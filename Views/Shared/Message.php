<div class="alert alert-<?= $message->Type->value ?> user-select-none alert-dismissible fade show d-flex" role="alert">
    <i class="bi <?= $message->Icon() ?> flex-shrink-0 me-2"></i>
    <div>
        <?= htmlspecialchars(string: $message->Content) ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>