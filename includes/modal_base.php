<?php
/**
 * Modal Base Component - Valley by Night
 * Provides minimal Bootstrap modal shell structure
 * JavaScript populates all content (title, body, footer)
 * 
 * @param string $modalId - ID for the modal element (default: 'vbnModal')
 * @param string $size - Modal size: 'sm', 'md', 'lg', 'xl' (default: 'md')
 * @param bool $fullscreen - Enable fullscreen modal (default: false)
 * @param bool $scrollable - Enable scrollable modal body (default: true)
 */

// Set defaults if not provided
$modalId = $modalId ?? 'vbnModal';
$size = $size ?? 'md';
$fullscreen = $fullscreen ?? false;
$scrollable = $scrollable ?? true;

// Build modal dialog classes
$dialogClasses = ['modal-dialog'];
if ($scrollable) {
    $dialogClasses[] = 'modal-dialog-scrollable';
}
if ($fullscreen) {
    $dialogClasses[] = 'modal-fullscreen';
} else {
    $dialogClasses[] = 'modal-' . $size;
}
$dialogClassString = implode(' ', $dialogClasses);

// Build aria-labelledby attribute
$labelId = $modalId . 'Label';
?>

<div class="modal fade" id="<?php echo htmlspecialchars($modalId); ?>" tabindex="-1" aria-labelledby="<?php echo htmlspecialchars($labelId); ?>" aria-hidden="true">
    <div class="<?php echo htmlspecialchars($dialogClassString); ?>">
        <div class="modal-content vbn-modal-content">
            <div class="modal-header vbn-modal-header">
                <h5 class="modal-title vbn-modal-title" id="<?php echo htmlspecialchars($labelId); ?>"></h5>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-light modal-fullscreen-btn" id="<?php echo htmlspecialchars($modalId); ?>FullscreenBtn" title="Toggle Fullscreen" aria-label="Toggle Fullscreen">
                        <i class="fas fa-expand modal-fullscreen-icon"></i>
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body vbn-modal-body"></div>
            <div class="modal-footer vbn-modal-footer"></div>
        </div>
    </div>
</div>

