document.addEventListener("DOMContentLoaded", () => {
  // ✅ POPUP logika
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


  // live search suggestions za all_transactions.php
  const searchInput = document.getElementById("search-input");
  const suggestionList = document.getElementById("suggestion-list");

  if (searchInput && suggestionList) {
    searchInput.addEventListener("input", () => {
      const query = searchInput.value.trim();

      if (query.length < 1) {
        suggestionList.style.display = "none";
        suggestionList.innerHTML = "";
        return;
      }

      fetch(`transaction_suggestions.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
          console.log("Fetched suggestions:", data);

          suggestionList.innerHTML = "";
          data.forEach((suggestion) => {
            const li = document.createElement("li");
            li.textContent = suggestion;
            li.classList.add("suggest-item");
            li.addEventListener("click", () => {
              searchInput.value = suggestion;
              suggestionList.innerHTML = "";
              suggestionList.style.display = "none";
              searchInput.form.submit(); 
            });
            suggestionList.appendChild(li);
          });

          suggestionList.style.display = "block";
        });
    });

    // ✅ handler za klik zunaj suggestion seznama
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
