<div id="ordine_header">
    <?php include $this->template('gestioneordini/gestione-header.tpl.php'); ?>
</div>

<?php if($this->statusObj->canRef_ModificaProdotti()): ?>
    <?php include $this->template('gestioneordini/prodotti.aperto.tpl.php'); ?>
<?php else: ?>
    <?php include $this->template('gestioneordini/prodotti.chiuso.tpl.php'); ?>
<?php endif; ?>
