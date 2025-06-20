document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.quantity-control').forEach(control => {
    const minusButton = control.querySelector('.qty-minus');
    const plusButton = control.querySelector('.qty-plus');
    const quantityInput = control.querySelector('input[name="quantity"]');

    minusButton.addEventListener('click', function () {
      let currentValue = parseInt(quantityInput.value);
      if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
      }
    });

    plusButton.addEventListener('click', function () {
      let currentValue = parseInt(quantityInput.value);
      quantityInput.value = currentValue + 1;
    });
  });
}); 