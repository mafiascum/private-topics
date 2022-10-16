document.addEventListener("DOMContentLoaded", function() {
    var $autolockTime = $("#autolock_time");
    var picker = new Pikaday({
        showSeconds: true,
        field: $autolockTime[0],
        format: "YYYY-MM-DD HH:mm:ss"
    });
});