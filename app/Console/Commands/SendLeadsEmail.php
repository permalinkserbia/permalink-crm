<?php

namespace App\Console\Commands;

use App\Mail\LeadEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Services\SettingService;

class SendLeadsEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:send-email {--limit=10 : Number of leads to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails to leads that have not been sent yet';

    protected $settingService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SettingService $settingService)
    {
        parent::__construct();
        $this->settingService = $settingService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');

        // Get email content and subject from settings
        $emailSubjectSetting = $this->settingService->get('lead_email_subject');
        $emailContentSetting = $this->settingService->get('lead_email_content');

        if (!$emailSubjectSetting || !$emailContentSetting) {
            Log::warning('Leads email not sent: subject or content not configured');
            $this->error("Email subject or content not configured. Please set 'lead_email_subject' and 'lead_email_content' settings in the admin panel.");
            return Command::FAILURE;
        }

        $emailSubject = $emailSubjectSetting->value;
        $emailContent = $emailContentSetting->value;

        if (empty($emailSubject) || empty($emailContent)) {
            Log::warning('Leads email not sent: subject or content is empty');
            $this->error("Email subject or content is empty. Please configure them in the admin panel.");
            return Command::FAILURE;
        }

        // Fetch leads that haven't been sent email yet
        $leads = Lead::where('email_sent', false)
            ->whereHas('person', function($query) {
                $query->whereHas('emails');
            })
            ->with(['person.emails' => function($query) {
                $query->where('primary', true);
            }])
            ->limit($limit)
            ->get();

        if ($leads->isEmpty()) {
            Log::info('Leads email: no leads found that need email sent');
            $this->info("No leads found that need email sent.");
            return Command::SUCCESS;
        }

        Log::info('Leads email: starting send', ['lead_count' => $leads->count(), 'limit' => $limit]);
        $this->info("Found {$leads->count()} lead(s) to send emails to.");

        $sentCount = 0;
        $failedCount = 0;

        foreach ($leads as $lead) {
            try {
                // Get primary email from person
                $primaryEmail = $lead->getPrimaryEmail();
                
                if (!$primaryEmail) {
                    Log::warning('Lead skipped: no email address', ['lead_id' => $lead->id, 'title' => $lead->title]);
                    $this->warn("Lead #{$lead->id} ({$lead->title}) has no email address. Skipping...");
                    $failedCount++;
                    continue;
                }

                // Get email address (handles encryption automatically)
                $emailAddress = $primaryEmail->address;
                
                if (empty($emailAddress)) {
                    Log::warning('Lead skipped: empty email address', ['lead_id' => $lead->id, 'title' => $lead->title]);
                    $this->warn("Lead #{$lead->id} ({$lead->title}) has empty email address. Skipping...");
                    $failedCount++;
                    continue;
                }

                // Replace placeholders in email content and subject
                $personName = $lead->person ? trim($lead->person->first_name . ' ' . $lead->person->last_name) : $lead->title;
                $personalizedContent = str_replace(
                    ['{{name}}', '{{lead_title}}', '{{lead_id}}'],
                    [$personName, $lead->title, $lead->lead_id ?? 'N/A'],
                    $emailContent
                );
                $personalizedSubject = str_replace(
                    ['{{name}}', '{{lead_title}}', '{{lead_id}}'],
                    [$personName, $lead->title, $lead->lead_id ?? 'N/A'],
                    $emailSubject
                );

                // Send email
                Log::info('Sending lead email', ['lead_id' => $lead->id, 'email' => $emailAddress]);
                $this->info("Sending email to lead #{$lead->id} ({$lead->title}) at {$emailAddress}...");
                
                Mail::to($emailAddress)->send(new LeadEmail($lead, $personalizedContent, $personalizedSubject));

                // Mark email as sent
                $lead->email_sent = true;
                $lead->save();

                $sentCount++;
                Log::info('Lead email sent', [
                    'lead_id' => $lead->id,
                    'email' => $emailAddress,
                    'subject' => $personalizedSubject,
                ]);
                $this->info("✓ Email sent successfully to lead #{$lead->id}");

                // Random delay between emails (1-20 seconds)
                if ($lead !== $leads->last()) {
                    $delay = rand(1, 20);
                    Log::info('Leads email: waiting before next send', ['seconds' => $delay]);
                    $this->info("Waiting {$delay} seconds before next email...");
                    sleep($delay);
                }

            } catch (\Exception $e) {
                Log::error('Lead email send failed', [
                    'lead_id' => $lead->id,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
                $this->error("Failed to send email to lead #{$lead->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        Log::info('Leads email: summary', ['sent' => $sentCount, 'failed' => $failedCount]);
        $this->info("\n=== Summary ===");
        $this->info("Successfully sent: {$sentCount}");
        $this->info("Failed: {$failedCount}");

        return Command::SUCCESS;
    }
}
