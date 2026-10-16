<select name="contractor_id" class="form-select" aria-label="Contractor" data-autosubmit @isset($clears) data-clears="{{ $clears }}" @endisset>
    <option value="">All contractors</option>
    @foreach ($contractors as $id => $name)
        <option value="{{ $id }}" @selected($filters['contractor_id'] === $id)>{{ $name }}</option>
    @endforeach
</select>
