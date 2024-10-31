// jQuery
$(document).ready(function() {
    // Acción para abrir el formulario y añadir el botón de cerrar
    $(".openform").click(function(event) {
        event.preventDefault(); // Evita el comportamiento por defecto del enlace
        $(".form").css("display","flex"); // Alterna la visibilidad del formulario

        // Comprueba si el formulario está visible y añade el botón de cerrar
        if ($(".form").is(":visible")) {
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
                $(".form").prepend(closeButton);
            }
        }
    });

    // Acción para cerrar el formulario al hacer clic en el botón de cerrar
    $(document).on("click", ".close-btn", function() {
        $(".form").hide(); // Esconde el formulario
        $(".close-btn").remove(); // Elimina el botón de cerrar
    });
});
