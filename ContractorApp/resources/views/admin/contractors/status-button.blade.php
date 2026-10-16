{{-- Activate / deactivate button with a confirmation that explains data is kept. Expects $contractor, optional $class. --}}
@if ($contractor->isActive())
    <x-delete-button :action="route('admin.contractors.status', $contractor)" :fields="['status' => 'inactive']" method="PATCH" label="Deactivate" icon="bi-pause-circle" variant="warning" loading-text="Updating…"
        :class="$class ?? 'btn btn-outline-warning btn-sm'" title="Deactivate this contractor?"
        :confirm="'“'.$contractor->company_name.'” users will be signed out and unable to sign in. All their data is kept, and you can reactivate them at any time.'" button="Deactivate" />
@else
    <x-delete-button :action="route('admin.contractors.status', $contractor)" :fields="['status' => 'active']" method="PATCH" label="Activate" icon="bi-play-circle" variant="success" loading-text="Updating…"
        :class="$class ?? 'btn btn-outline-success btn-sm'" title="Activate this contractor?"
        :confirm="'“'.$contractor->company_name.'” users will be able to sign in again.'" button="Activate" />
@endif
