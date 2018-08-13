<div class="modal fade" tabindex="-1" role="dialog" id="enderecoAddressCheckModal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title">Ist Ihre Rechnungsadresse korrekt?</h4>
			</div>
			<div class="modal-body">
				<div class="panel-body">
					<span class="description-text">Ihre Eingabe</span>
					<div id="enderecoCurrentInput" class="form-group"></div>
					<span class="description-text">Unsere Vorschläge</span>
					<div id="enderecoCorrectedSuggestions" class="form-group"></div>
					<div class="could-not-find-corrections">Die eingegebene Adresse konnte nicht gefunden werden. Bitte überprüfen sie ihre Eingabe erneut. Wenn Sie sich dennoch sicher sind, das die Eingabe korrekt ist, fahren sie einfach fort.</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="enderecoAddressCheckSubmit">Adresse übernehmen</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
