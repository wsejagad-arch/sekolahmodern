<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Test Select2</title>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>

<select id="pilihSiswa" style="width:300px"></select>

<script>
$(document).ready(function() {
    $('#pilihSiswa').select2({
        placeholder: "Ketik nama siswa...",
        ajax: {
            url: "/cari_siswa.php",
            type: "GET",
            dataType: "json",
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return data;
            }
        }
    });
});
</script>

</body>
</html>

