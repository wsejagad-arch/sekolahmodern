<?php
$guru = file_get_contents('pages/guru/guru_2026.php');

$js = '
<script>
function toggleNotif(event) {
    event.stopPropagation();
    var dropdown = document.getElementById("notifDropdownDesktop");
    if(dropdown) {
        dropdown.classList.toggle("show");
    }
}
window.addEventListener("click", function(e) {
    var dropdown = document.getElementById("notifDropdownDesktop");
    if(dropdown && dropdown.classList.contains("show")) {
        dropdown.classList.remove("show");
    }
});
</script>
</body>';

$guru = preg_replace('/<\/body>/i', $js, $guru);

file_put_contents('pages/guru/guru_2026.php', $guru);
echo "Injected JS successfully.\n";
