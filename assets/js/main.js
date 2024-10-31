$(document).ready(function() {
    $(".openform").click(function(event) {
        event.preventDefault(); // Evita el comportamiento por defecto del enlace
        $(".form").css("display","flex"); // Alterna la visibilidad del formulario
    });
});
