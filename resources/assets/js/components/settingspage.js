$(document).ready(function () {
    $('#avatarInput').on('change', function(e) {
        var file = document.getElementById('avatarInput').files[0];
        var reader = new FileReader();

        reader.addEventListener("load", function() {
            $('#previewAvatar').html('<img src="' + reader.result + '" class="rounded-circle box-shadow" />');
        }, false);

        if (file) {
            reader.readAsDataURL(file);
        }
    });
});
