<div class="modal fade" tabindex="-1" role="dialog" id="enderecoAddressCheckModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="panel-body">
                    <h4 class="modal-title"></h4>
                    <span class="description-text"><?php _e( 'Your choice', 'adress_autocompletion'  ); ?></span>
                    <div class="enderecoCurrentInput" class="form-group"></div>
                    <span class="description-text"><?php _e( 'Correction', 'adress_autocompletion'  ); ?></span>
                    <div class="enderecoCorrectedSuggestions" class="form-group"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="enderecoAddressCheckSubmit"><?php _e( 'Confirm address', 'adress_autocompletion'  ); ?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
