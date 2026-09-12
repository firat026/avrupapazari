(function () {
  var country = document.querySelector('select[data-city-target]');
  if (!country) return;
  var city = document.getElementById(country.dataset.cityTarget);
  var base = document.querySelector('.site-header .brand').getAttribute('href').replace(/\/$/, '');
  country.addEventListener('change', function () {
    var first = city.options[0];
    city.innerHTML = '';
    city.appendChild(first);
    city.disabled = !country.value;
    if (!country.value) return;
    fetch(base + '/ajax/get-cities.php?country_id=' + encodeURIComponent(country.value), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (list) {
        if (!Array.isArray(list)) return;
        list.forEach(function (c) { var o = document.createElement('option'); o.value = c.id; o.textContent = c.name; city.appendChild(o); });
      });
  });
})();
