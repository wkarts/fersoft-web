<div class="form-group validated col-12 col-md-3">
    <label class="col-xl-12 col-lg-12 col-form-label text-left">{{ $title }}</label>
    <div class="col-lg-12 col-xl-12">
        <div class="image-input image-input-outline" id="image-container-{{ $id }}">
            <div class="image-input-wrapper" id="image-wrapper-{{ $id }}"
                 style="background-image: url({{ $imageUrl }});"></div>

            <!-- Botão para alterar a imagem -->
            <label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow"
                   data-action="change" data-toggle="tooltip" title="Alterar imagem">
                <i class="fa fa-pencil icon-sm text-muted"></i>
                <input type="file" name="{{ $inputName }}" accept=".png, .jpg, .jpeg"
                       id="file-input-{{ $id }}"
                       onchange="
                            const fileInput = document.getElementById('file-input-{{ $id }}');
                            const imageWrapper = document.getElementById('image-wrapper-{{ $id }}');
                            const cancelButton = document.getElementById('cancel-{{ $id }}');
                            if (fileInput.files && fileInput.files[0]) {
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    imageWrapper.style.backgroundImage = `url('${e.target.result}')`;
                                    cancelButton.style.display = 'inline-block';
                                };
                                reader.readAsDataURL(fileInput.files[0]);
                            }
                        ">
            </label>

            <!-- Botão para remover a imagem -->
            @if($imageUrl !== '/imgs/no_image.png')
                <button type="button" id="remove-{{ $id }}"
                        class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow"
                        data-toggle="tooltip" title="Remover imagem"
                        onclick="document.getElementById('image-wrapper-{{ $id }}').style.backgroundImage = 'url(/imgs/no_image.png)';
                                 document.getElementById('remove-image-input-{{ $id }}').value = '1';
                                 document.getElementById('cancel-{{ $id }}').style.display = 'inline-block';">
                    <i class="fa fa-trash icon-xs text-muted"></i>
                </button>

                <!-- Botão para download da imagem -->
                <a href="{{ $imageUrl }}" download class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow"
                   data-toggle="tooltip" title="Download imagem">
                    <i class="fa fa-download icon-xs text-muted"></i>
                </a>

                <!-- Botão para ampliar a imagem -->
                <button type="button" class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow"
                        title="Ampliar imagem" data-toggle="modal" data-target="#imageModal">
                    <i class="fa fa-search icon-xs text-muted"></i>
                </button>
            @endif

            <!-- Botão de cancelar que restaura a imagem original -->
            <button type="button" id="cancel-{{ $id }}" style="display:none;"
                    class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow"
                    data-toggle="tooltip" title="Cancelar"
                    onclick="document.getElementById('image-wrapper-{{ $id }}').style.backgroundImage = 'url({{ $imageUrl }})';
                             document.getElementById('remove-image-input-{{ $id }}').value = '0';
                             document.getElementById('file-input-{{ $id }}').value = '';
                             this.style.display = 'none';">
                <i class="fa fa-close icon-xs text-muted"></i>
            </button>
        </div>

        <span class="form-text text-muted">.png, .jpg, .jpeg</span>
        @if($errors->has($inputName))
            <div class="invalid-feedback">
                {{ $errors->first($inputName) }}
            </div>
        @endif
    </div>
    <input type="hidden" name="remove_image" id="remove-image-input-{{ $id }}" value="0">
</div>
