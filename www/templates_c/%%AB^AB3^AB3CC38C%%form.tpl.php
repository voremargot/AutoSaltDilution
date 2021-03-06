<form id="<?php echo $this->_tpl_vars['Grid']['FormId']; ?>
" class="<?php if ($this->_tpl_vars['Grid']['FormLayout']->isHorizontal()): ?>form-horizontal<?php endif; ?>" enctype="multipart/form-data" method="POST" action="<?php echo $this->_tpl_vars['Grid']['FormAction']; ?>
">

    <?php if (! $this->_tpl_vars['isEditOperation'] && $this->_tpl_vars['Grid']['AllowAddMultipleRecords']): ?>
        <div class="btn-group pull-right form-collection-actions">
            <button type="button" class="btn btn-default icon-copy js-form-copy" title=<?php echo $this->_tpl_vars['Captions']->GetMessageString('Copy'); ?>
></button>
            <button type="button" class="btn btn-default icon-remove js-form-remove" style="display: none" title=<?php echo $this->_tpl_vars['Captions']->GetMessageString('Delete'); ?>
></button>
        </div>
    <?php endif; ?>
    <div class="clearfix"></div>

    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => 'common/messages_block.tpl', 'smarty_include_vars' => array('GridMessages' => $this->_tpl_vars['Grid'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

    <?php if ($this->_tpl_vars['ShowErrorsOnTop']): ?>
        <div class="row">
            <div class="col-md-12 form-error-container form-error-container-top"></div>
        </div>
    <?php endif; ?>

    <?php if ($this->_tpl_vars['isMultiEditOperation']): ?>
        <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => 'forms/fields_to_be_updated.tpl', 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
    <?php endif; ?>

    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => 'forms/form_fields.tpl', 'smarty_include_vars' => array('isViewForm' => false)));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => 'forms/form_footer.tpl', 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

    <?php if ($this->_tpl_vars['flashMessages']): ?>
        <input type="hidden" name="flash_messages" value="1" />
    <?php endif; ?>

    <?php if ($this->_tpl_vars['ShowErrorsAtBottom']): ?>
        <div class="row">
            <div class="col-md-12 form-error-container form-error-container-bottom"></div>
        </div>
    <?php endif; ?>

    <?php if (! $this->_tpl_vars['isMultiUploadOperation']): ?>
        <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => 'forms/form_scripts.tpl', 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
    <?php endif; ?>

</form>