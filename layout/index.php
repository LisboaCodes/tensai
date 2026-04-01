<?php
$arquivos = ["materiais.html", "ferramentas.html", "suporte.html", "tensai.html", "login.html", ]; // Lista dos arquivos

echo "<ul>";
foreach ($arquivos as $arquivo) {
    echo "<li><a href='$arquivo' target='_blank'>$arquivo</a></li>";
}
echo "</ul>";
?>
