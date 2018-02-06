document.addEventListener("DOMContentLoaded", function() {
    var $autolockTime = $("#autolock_time");
    var timezoneOffset = $autolockTime.data("time-zone");
    var picker = new Pikaday({
        showSeconds: true,
        field: $autolockTime[0],
        format: "YYYY-MM-DD HH:mm:ss " + timezoneOffset
    });
});