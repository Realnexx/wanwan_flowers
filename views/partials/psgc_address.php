<?php
require_once __DIR__.'/../../models/Psgc.php';
$psgcEscape=static function($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$selectedRegion=$addressValues['psgc_region']??'';$selectedProvince=$addressValues['psgc_province']??'';$selectedCity=$addressValues['psgc_city']??'';
?>
<div class="psgc-address checkout-fields" data-psgc-address data-endpoint="ajax/psgc.php">
<?php foreach (['region'=>'Region','province'=>'Province','city'=>'City / municipality','barangay'=>'Barangay'] as $level=>$label):
$key='psgc_'.$level;$selected=$addressValues[$key]??'';
$options=$level==='region'?Psgc::options('regions'):($level==='province'?Psgc::options('provinces',$selectedRegion):($level==='city'?Psgc::options('cities',$selectedRegion,$selectedProvince):Psgc::options('barangays',$selectedCity)));
?>
<label class="checkout-field" for="address-<?= $level ?>"><span><?= $label ?></span><select id="address-<?= $level ?>" name="<?= $key ?>" data-address-level="<?= $level ?>" required aria-describedby="error-<?= $key ?>"><option value="">Select <?= strtolower($label) ?></option><?php foreach($options as $option): ?><option value="<?= $psgcEscape($option['code']) ?>" <?= $selected===$option['code']?'selected':'' ?>><?= $psgcEscape($option['name']) ?></option><?php endforeach; ?></select><small class="field-error" id="error-<?= $key ?>" aria-live="polite"><?= $psgcEscape($addressErrors[$key]??'') ?></small></label>
<?php endforeach; ?>
<p class="psgc-address-status fineprint" role="status"></p><button class="text-link psgc-retry" type="button" hidden>Retry loading addresses</button>
</div>
