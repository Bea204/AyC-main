// Script específico para formularios de edición
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const btnActualizar = document.getElementById('btnActualizar');
    
    if (form && btnActualizar) {
        // Remover cualquier event listener previo
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        // Agregar nuestro propio manejador
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (confirm('¿Deseas guardar los cambios?')) {
                console.log('Enviando formulario de edición...');
                this.submit();
            }
        });
    }
}); 