document.addEventListener("DOMContentLoaded", () => {
  // ✅ POPUP LOGIC
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

  // ✅ SIDEBAR TOGGLE LOGIC
  const sidebarToggle = document.getElementById("sidebarToggle");
  const body = document.body;

  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", (e) => {
      e.stopPropagation();
      body.classList.toggle("sidebar-open");
    });
  }

  document.addEventListener("click", (e) => {
    const sidebar = document.getElementById("sidebar");
    if (sidebar && !sidebar.contains(e.target) && !e.target.closest('#sidebarToggle')) {
      body.classList.remove("sidebar-open");
    }
  });

  const sidebar = document.getElementById("sidebar");
  if (sidebar) {
    sidebar.addEventListener("click", (e) => {
      e.stopPropagation();
    });
  }

  // ✅ LIVE SEARCH SUGGESTIONS (all_transactions.php)
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
              searchInput.form.submit(); // Trigger search
            });
            suggestionList.appendChild(li);
          });

          suggestionList.style.display = "block";
        });
    });

    // ✅ Safely handle outside click
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
