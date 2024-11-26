$(document).ready(function() {
    function starttracking() {
        setInterval(myTimer, 60000*1); //60000 = 1 minute
    }
    function myTimer() {
      $.ajax({
        type: "POST",
        url: "event_php/api",
        data: {
          "time":1,
        },
        success: function(data) {
        },
      });
    }
    starttracking();
});
