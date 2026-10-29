<?php require_once __DIR__ . '/../../models/FlowerCatalog.php'; ?>
<fieldset class="w-100 border rounded p-3 my-2">
    <legend class="float-none w-auto px-2 fs-6">Website flower details</legend>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="<?= $flowerFieldPrefix ?>website_badge">Bouquet badge</label>
            <select class="form-select" name="website_badge" id="<?= $flowerFieldPrefix ?>website_badge">
                <?php foreach (FlowerCatalog::BADGES as $badge): ?><option value="<?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($badge ?: 'No badge', ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4"><label class="form-label" for="<?= $flowerFieldPrefix ?>website_occasion">Occasion</label><input class="form-control" name="website_occasion" id="<?= $flowerFieldPrefix ?>website_occasion" maxlength="80" placeholder="e.g. Birthday, Romance"></div>
        <div class="col-md-4"><label class="form-label" for="<?= $flowerFieldPrefix ?>website_stems">Stem details</label><input class="form-control" name="website_stems" id="<?= $flowerFieldPrefix ?>website_stems" maxlength="255" placeholder="e.g. 14–16 stems · Roses & eucalyptus"></div>
    </div>
    <p class="small text-muted mb-0 mt-2">Enable Show on website to publish. Name, price, description, and uploaded image are shared with the website. Badge labels are selected manually.</p>
</fieldset>
