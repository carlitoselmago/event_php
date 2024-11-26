// jQuery
$(document).ready(function() {
    // Acción para abrir el formulario y añadir el botón de cerrar
    $(".openform").click(function(event) {
        event.preventDefault(); // Evita el comportamiento por defecto del enlace
        $(".registerform").css("display","flex"); // Alterna la visibilidad del formulario

        // Comprueba si el formulario está visible y añade el botón de cerrar
        if ($(".registerform").is(":visible")) {
            // Añadir el botón de cerrar solo si no existe ya
            if (!$(".close-btn").length) {
                const closeButton = `
                    <div class="close-btn" style="position: absolute; top: 10px; right: 10px; cursor: pointer;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6L6 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                `;
                $(".registerform").prepend(closeButton);
            }
        }
    });

    // Acción para cerrar el formulario al hacer clic en el botón de cerrar
    $(document).on("click", ".close-btn", function() {
        $(".registerform").hide(); // Esconde el formulario
        $(".close-btn").remove(); // Elimina el botón de cerrar
    });

    // Acción para cerrar el formulario al hacer clic fuera de <form> pero dentro de .form
    $(".registerform").on("click", function(event) {
        // Cierra el formulario solo si el clic fue en .form y no dentro de <form>
        if ($(event.target).is(".registerform")) {
            $(".registerform").hide(); // Esconde el formulario
            $(".close-btn").remove(); // Elimina el botón de cerrar
        }
    });
});
