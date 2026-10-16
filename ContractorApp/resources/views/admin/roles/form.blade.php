{{-- Role form. Expects $role, $groups (catalogue), $selected (permission names). --}}
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Role" :first="true">
            <x-form.input name="name" label="Role Name" :value="$role->name" :required="true" maxlength="60" autofocus />
            @if ($role->exists)
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Portal</label>
                    <input type="text" class="form-control" value="{{ $role->type->label() }}" disabled />
                    <div class="form-text">A role's portal cannot be changed after it is created.</div>
                </div>
            @else
                <x-form.select name="type" label="Portal" :options="App\Enums\RoleType::options()" :value="$role->type" :required="true" data-role-type help="Contractor roles are for contractor users; admin roles for internal staff." />
            @endif
            <x-form.input name="description" label="Description" :value="$role->description" maxlength="250" col="col-12" />
        </x-form.section>

        <hr class="form-divider" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="form-section-title mb-0">Permissions</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-check-all>Select all</button>
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-check-none>Clear</button>
            </div>
        </div>
        @error('permissions.*') <div class="alert alert-danger small">{{ $message }}</div> @enderror
        <div class="row g-3">
            @foreach ($groups as $key => $group)
                <div class="col-sm-6 col-lg-4 col-xxl-3" data-permission-group data-portals="{{ implode(' ', $group['portals']) }}">
                    <div class="permission-group">
                        <h3>{{ $group['label'] }}</h3>
                        @foreach ($group['abilities'] as $ability)
                            @php($name = "{$key}.{$ability}")
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $name }}" id="perm-{{ $key }}-{{ $ability }}" @checked(in_array($name, $selected, true)) />
                                <label class="form-check-label" for="perm-{{ $key }}-{{ $ability }}">{{ ucfirst($ability) }} <span class="text-muted small">({{ $name }})</span></label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const typeSelect = document.querySelector('[data-role-type]');
        const currentType = @json($role->type?->value);
        const groups = document.querySelectorAll('[data-permission-group]');
        const sync = () => {
            const type = typeSelect ? typeSelect.value : currentType;
            groups.forEach((group) => {
                const allowed = group.dataset.portals.split(' ').includes(type);
                group.hidden = !allowed;
                group.querySelectorAll('input').forEach((input) => { input.disabled = !allowed; if (!allowed) input.checked = false; });
            });
        };
        typeSelect?.addEventListener('change', sync);
        sync();
        const visibleBoxes = () => [...document.querySelectorAll('[data-permission-group]:not([hidden]) input')];
        document.querySelector('[data-check-all]').addEventListener('click', () => visibleBoxes().forEach((i) => { i.checked = true; }));
        document.querySelector('[data-check-none]').addEventListener('click', () => visibleBoxes().forEach((i) => { i.checked = false; }));
    })();
</script>
@endpush
