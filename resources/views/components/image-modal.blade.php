<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">{{ $title }}</h5>
                <button type="button" class="btn btn-light btn-icon btn-sm rounded-circle" data-dismiss="modal" aria-label="Close" style="position: absolute; top: 10px; right: 10px;">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="modal-body text-center">
                <img src="{{ $imageUrl }}" id="modalImage" class="img-fluid" alt="{{ $title }}">
            </div>

        </div>
    </div>
</div>
