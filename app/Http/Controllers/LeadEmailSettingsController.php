<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLeadEmailSettingsRequest;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Services\SettingService;

class LeadEmailSettingsController extends Controller
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Show the form for editing lead email settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $emailSubjectSetting = $this->settingService->get('lead_email_subject');
        $emailContentSetting = $this->settingService->get('lead_email_content');

        return view('lead-email-settings.edit', [
            'emailSubject' => $emailSubjectSetting ? $emailSubjectSetting->value : '',
            'emailContent' => $emailContentSetting ? $emailContentSetting->value : '',
        ]);
    }

    /**
     * Update the lead email settings.
     *
     * @param  \App\Http\Requests\UpdateLeadEmailSettingsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLeadEmailSettingsRequest $request)
    {
        $this->settingService->set('lead_email_subject', $request->email_subject, 'Lead Email Subject');
        $this->settingService->set('lead_email_content', $request->email_content, 'Lead Email Content');

        return redirect()->route('lead-email-settings.edit')
            ->with('success', 'Lead email settings updated successfully!');
    }
}
