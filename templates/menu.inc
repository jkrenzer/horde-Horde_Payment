<div id="menu">
 <?php echo Horde_Payment::getMenu('string') ?>
</div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
