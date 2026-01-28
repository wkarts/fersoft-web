<div class="d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="card card-custom gutter-b example example-compact">
        <form method="POST" action="{{ $formAction }}">
            @csrf
            <h3>{{ $title }}</h3>
            <div>
                @foreach($fields as $field)
                    <div>
                        <label>{{ $field['label'] }}</label>
                        @if($field['type'] === 'select')
                            <select name="{{ $field['name'] }}">
                                @foreach($field['options'] as $option)
                                    <option value="{{ $option['value'] }}"
                                        {{ isset($data->{$field['name']}) && $data->{$field['name']} == $option['value'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif($field['type'] === 'textarea')
                            <textarea name="{{ $field['name'] }}">{{ $data->{$field['name']} ?? '' }}</textarea>
                        @else
                            <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ $data->{$field['name']} ?? '' }}">
                        @endif
                    </div>
                @endforeach
            </div>
            <button type="submit">Salvar</button>
            <a href="{{ $cancelUrl }}">Cancelar</a>
        </form>
    </div>
</div>
