<form class="needs-validation text-left" novalidate action="javascript:void(0);" id="formConvenants">
    @csrf
    <div class="form-row">
        <div class="col-md-4 mb-4">
            <label for="associate">Associado <b class="error">*</b></label>
            <select class="form-control" required id="associate" name="associate">
                <option value="">-Selecione-</option>
                @foreach ($associateList as $item)
                    <option value="{{ $item->assoc_codigoid }}">{{ $item->assoc_nome }}</option>
                @endforeach
            </select>

        </div>
        <div class="col-md-4 mb-4">
            <label for="convenants">Convênio <b class="error">*</b></label>
            <select class="form-control" required id="convenants" name="convenants" style="z-index: 99999!important;">
                <option value="">-Selecione-</option>
                @foreach ($agreementList as $item)
                    <option value="{{ $item->con_codigoid }}">{{ $item->con_nome }}</option>
                @endforeach
            </select>

        </div>
        <div class="col-md-4 mb-4">
            <label for="email">Valor da parcela <b class="error">*</b></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="inputGroupPrepend">R$</span>
                </div>
                <input type="text" class="form-control" id="portion" name="portion"  required>

            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-4 mb-4">
            <label for="validationCustom03">Nº de Parcelas <b class="error">*</b></label>
            <input type="number" class="form-control" id="number" name="number" required>
        </div>
        <div class="col-md-4 mb-4">
            <label for="validationCustom04">Valor Total <b class="error">*</b></label>
            <input type="text" class="form-control"  id="total" name="total" placeholder="****" required>
        </div>
        <div class="col-md-4 mb-4">
            <label for="validationCustom05">Vencimento Final<b class="error">*</b></label>
            <input type="text" class="form-control"  id="duedate" name="duedate" placeholder="00/00/0000" required>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i>Limpar</button>
        <button class="btn btn-primary" id="btnSave" type="submit">Salvar</button>
    </div>
</form>
