document.addEventListener("DOMContentLoaded", () => {
  // Logika za prikaz in skrivanje sporočil
  const popup = document.getElementById("popup");
  if (popup) {
    popup.style.opacity = "1";
    popup.style.transition = "opacity 0.5s ease";
    setTimeout(() => {
      popup.style.opacity = "0";
      setTimeout(() => {
        if (popup && popup.parentNode) {
          popup.parentNode.removeChild(popup);
        }
      }, 500);
    }, 3000);
  }

  // Funkcionalnost predlogov za iskanje transakcij
  const searchInput = document.getElementById("search-input");
  const suggestionList = document.getElementById("suggestion-list");

  if (searchInput && suggestionList) {
    // Dogodek ob vnosu v iskalno polje
    searchInput.addEventListener("input", () => {
      const query = searchInput.value.trim();

      // Ne prikazuj predlogov, če je iskalni niz prazen
      if (query.length < 1) {
        suggestionList.style.display = "none";
        suggestionList.innerHTML = "";
        return;
      }

      // Pridobi predloge preko AJAX
      fetch(`transaction_suggestions.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
          console.log("Fetched suggestions:", data);

          // Prikaži predloge v spustnem seznamu
          suggestionList.innerHTML = "";
          data.forEach((suggestion) => {
            const li = document.createElement("li");
            li.textContent = suggestion;
            li.classList.add("suggest-item");
            // Dogodek ob kliku na predlog
            li.addEventListener("click", () => {
              searchInput.value = suggestion;
              suggestionList.innerHTML = "";
              suggestionList.style.display = "none";
              searchInput.form.submit(); // Pošlji obrazec za iskanje
            });
            suggestionList.appendChild(li);
          });

          suggestionList.style.display = "block";
        });
    });

    // Dogodek za klik zunaj seznama predlogov - skrije predloge
    document.addEventListener("click", (e) => {
      if (
        suggestionList &&
        searchInput &&
        !suggestionList.contains(e.target) &&
        e.target !== searchInput
      ) {
        suggestionList.innerHTML = "";
        suggestionList.style.display = "none";
      }
    });
  }
});