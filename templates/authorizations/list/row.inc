<tr>
<td>
<?php
if ($void) {
    echo Horde::link(Horde_Util::addParameter($void_url, 'authorization_id',  $row['authorization_id']), sprintf(_("Void %s"), $row['authorization_id'])) .
    Horde::img('cross.png', sprintf(_("Void %s"), $row['authorization_id']), '', $img_dir) . '</a> ' . "\n";
}
if ($read) {
    echo Horde::link('#', sprintf(_("Details for %s"), $row['authorization_id']), '', '', "popup('" . Horde_Util::addParameter($details_url, 'authorization_id',  $row['authorization_id'], false) . "')") .
    Horde::img('info.png', sprintf(_("Details for %s"), $row['authorization_id']), '', $img_dir) . '</a> ' . "\n";
}
if ($delete) {
    echo Horde::link(Horde_Util::addParameter($delete_url, 'authorization_id',  $row['authorization_id']), sprintf(_("Delete %s"), $row['authorization_id'])) .
    Horde::img('delete.png', sprintf(_("Delete %s"), $row['authorization_id']), '', $img_dir) . '</a> ' . "\n";
}
?>
</td>
<td><?php echo $registry->get('name', $row['module_name']) ?></td>
<td><?php echo $row['authorization_id'] ?></td>
<td>
<?php

echo $row['module_id'];

if ($referrence_name) {
    $name = $registry->callByPackage($row['module_name'], 'getName', array($row['module_id']));
    if ($name instanceof PEAR_Error) {
        echo ' (' . $name->getMessage() . ')';
    } elseif (!empty($name)) {
        echo ' (' . $name . ')';
    }
}

?></td>
<td><?php echo Horde_Payment::formatPrice($row['amount']) ?></td>
<td><?php echo $row['user_uid'] ?></td>
<td><?php echo Horde_Payment::formatDate($row['created']) ?></td>
<td><?php echo Horde_Payment::formatDate($row['updated']) ?></td>
<td><?php echo Horde_Payment::getStatusName($row['status']) ?></td>
<td><?php echo $row['method'] ?></td>
</tr>
