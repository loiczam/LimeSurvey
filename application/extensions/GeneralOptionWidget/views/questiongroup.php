<div class="form-row">
    <i class="fa fa-question pull-right" ></i>
    <label class="form-label" :for="elId">
        <?= $this->generalOption->title; ?>
    </label>
    <select 
        class="form-control"
        name="<?= $this->generalOption->name; ?>"
        id="<?= $this->generalOption->name; ?>"
    >
        <!-- TODO -->
        <?php foreach ($this->generalOption->formElement->options['options']->options as $option): ?>
            <option value="<?= $option->value; ?>"><?= $option->text; ?></option>
        <?php endforeach; ?>
    </select>
    <div id="general-setting-help-<?= $this->generalOption->name; ?>" class="question-option-help well" /><?= $this->generalOption->formElement->help; ?></div>
</div>
