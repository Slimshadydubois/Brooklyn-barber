<?php
$file = "barbeiro_dashboard.php";
$content = file_get_contents($file);
$find = '<div class="service-actions" style="justify-content: flex-end;">';
$replace = '<div class="service-actions" style="justify-content: flex-end;">
<?php if (is_null($servico[''id_barbeiro''])): ?>
    <span style="font-size: 0.8rem; background: #333; padding: 4px 8px; border-radius: 4px; color: #fff;">Padrão Geral</span>
<?php else: ?>';
$content = str_replace($find, $replace, $content);

$find2 = '</form>
                                                </div>';
$replace2 = '</form>
<?php endif; ?>
                                                </div>';
$content = str_replace($find2, $replace2, $content);
file_put_contents($file, $content);
echo "Done";
?>
