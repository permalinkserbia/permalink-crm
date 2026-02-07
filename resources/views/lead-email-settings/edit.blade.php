@extends('laravel-crm::layouts.app')

@section('content')
<form method="POST" action="{{ route('lead-email-settings.update') }}">
    @csrf
    @method('PATCH')

    <div class="container-fluid pl-0">
        <div class="row">
            <div class="col col-md-2">
                <div class="card">
                    <div class="card-body py-3 px-2">
                        <ul class="nav nav-pills flex-column" role="tablist">
                            @can('view crm settings')
                            <li class="nav-item">
                                <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.settings') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.settings.edit')) }}" role="tab" aria-controls="settings" aria-selected="true">{{ ucwords(__('laravel-crm::lang.general_settings')) }}</a>
                            </li>
                            @endcan
                            @can('update', \VentureDrake\LaravelCrm\Models\Setting::class)
                            <li class="nav-item">
                                <a class="nav-link {{ (Route::currentRouteName() === 'lead-email-settings.edit') ? 'active' : '' }}" href="{{ route('lead-email-settings.edit') }}" role="tab" aria-controls="lead-email-settings" aria-selected="false">Lead Email Settings</a>
                            </li>
                            @endcan
                            @can('view crm roles')
                                <li class="nav-item">
                                    <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.roles') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.roles.index')) }}" role="tab" aria-controls="roles" aria-selected="false">{{ ucwords(__('laravel-crm::lang.roles_and_permissions')) }}</a>
                                </li>
                            @endcan
                            @can('view crm pipelines')
                                <li class="nav-item">
                                    <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.pipelines') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.pipelines.index')) }}" role="tab" aria-controls="pipelines" aria-selected="false">{{ ucwords(__('laravel-crm::lang.pipelines')) }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.pipeline-stages') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.pipeline-stages.index')) }}" role="tab" aria-controls="pipeline-stages" aria-selected="false">{{ ucwords(__('laravel-crm::lang.pipeline_stages')) }}</a>
                                </li>
                            @endcan
                            @can('view crm product categories')
                            <li class="nav-item">
                                <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.product-categories') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.product-categories.index')) }}" role="product-categories" aria-controls="product-categories" aria-selected="false">{{ ucwords(__('laravel-crm::lang.product_categories')) }}</a>
                            </li>
                            @endcan
                            @can('view crm tax rates')
                            <li class="nav-item">
                                <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.tax-rates') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.tax-rates.index')) }}" role="tax-rates" aria-controls="tax-rates" aria-selected="false">{{ ucwords(__('laravel-crm::lang.tax_rates')) }}</a>
                            </li>
                            @endcan
                            @can('view crm labels')
                            <li class="nav-item">
                                <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.labels') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.labels.index')) }}" role="tab" aria-controls="labels" aria-selected="false">{{ ucwords(__('laravel-crm::lang.labels')) }}</a>
                            </li>
                            @endcan
                            @can('view crm fields')
                               <li class="nav-item">
                                    <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.fields') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.fields.index')) }}" role="tab" aria-controls="fields" aria-selected="false">{{ ucwords(__('laravel-crm::lang.custom_fields')) }}</a>
                               </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.field-groups') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.field-groups.index')) }}" role="tab" aria-controls="field-groups" aria-selected="false">{{ ucwords(__('laravel-crm::lang.custom_field_groups')) }}</a>
                                </li>
                            @endcan
                            @can('view crm integrations')
                            <li class="nav-item">
                                <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.integrations.xero') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.integrations.xero')) }}" role="tab" aria-controls="integrations" aria-selected="false">{{ ucwords(__('laravel-crm::lang.integrations')) }}</a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title float-left m-0">Lead Email Settings</h3>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="email_subject">Email Subject <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('email_subject') is-invalid @enderror"
                                   id="email_subject"
                                   name="email_subject"
                                   value="{{ old('email_subject', $emailSubject) }}"
                                   required>
                            @error('email_subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                You can use placeholders: @{{name}}, @{{lead_title}}, @{{lead_id}}
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="email_content">Email Content <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('email_content') is-invalid @enderror"
                                      id="email_content"
                                      name="email_content"
                                      rows="15"
                                      required>{{ old('email_content', $emailContent) }}</textarea>
                            @error('email_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                You can use placeholders: @{{name}}, @{{lead_title}}, @{{lead_id}}
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <h5>Available Placeholders:</h5>
                            <ul class="mb-0">
                                <li><strong>@{{name}}</strong> - Person's full name</li>
                                <li><strong>@{{lead_title}}</strong> - Lead title</li>
                                <li><strong>@{{lead_id}}</strong> - Lead ID (e.g., L1000)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Save Changes
                        </button>
                        <a href="{{ route('laravel-crm.settings.edit') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

