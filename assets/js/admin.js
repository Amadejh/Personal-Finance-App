// ✅ Load users via AJAX
function loadUsers(search = '', page = 1) {
  $.get("search_users.php", { search, page }, function (data) {
    $("#user-list").html(data);
  });
}

// ✅ Autocomplete handler
function setupAutocomplete(inputId) {
  const input = document.getElementById(inputId);
  let currentFocus = -1;

  input.addEventListener("input", function () {
    const val = this.value;
    if (!val) return closeAllLists();

    $.get("user_suggestions.php", { q: val }, function (data) {
      const suggestions = JSON.parse(data);
      closeAllLists();

      const list = document.createElement("DIV");
      list.setAttribute("id", inputId + "-autocomplete-list");
      list.setAttribute("class", "suggestions-box animated-dropdown"); // ✅ Updated class name
      input.parentNode.appendChild(list);

      suggestions.forEach(suggestion => {
        const item = document.createElement("DIV");
        item.innerHTML = `<strong>${suggestion.label.substr(0, val.length)}</strong>${suggestion.label.substr(val.length)}`;
        item.innerHTML += `<input type="hidden" value="${suggestion.value}">`;
        item.setAttribute("data-value", suggestion.value);

        item.addEventListener("click", function () {
          input.value = this.getAttribute("data-value");
          closeAllLists();
          loadUsers(input.value);
        });

        list.appendChild(item);
      });
    });
  });

  input.addEventListener("keydown", function (e) {
    let x = document.getElementById(inputId + "-autocomplete-list");
    if (x) x = x.getElementsByTagName("div");

    if (e.key === "ArrowDown") {
      currentFocus++;
      addActive(x);
    } else if (e.key === "ArrowUp") {
      currentFocus--;
      addActive(x);
    } else if (e.key === "Enter") {
      e.preventDefault();
      if (currentFocus > -1 && x) x[currentFocus].click();
    }
  });

  function addActive(x) {
    if (!x) return false;
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = x.length - 1;
    x[currentFocus].classList.add("autocomplete-active");
  }

  function removeActive(x) {
    for (const el of x) el.classList.remove("autocomplete-active");
  }

  function closeAllLists(elmnt) {
    const items = document.getElementsByClassName("suggestions-box");
    for (const item of items) {
      if (elmnt !== item && elmnt !== input) {
        item.parentNode.removeChild(item);
      }
    }
  }

  document.addEventListener("click", function (e) {
    closeAllLists(e.target);
  });
}

// ✅ jQuery on ready
$(function () {
  loadUsers();
  setupAutocomplete("search-input");

  // Manual typing fallback
  $("#search-input").on("keyup", function () {
    const value = $(this).val();
    loadUsers(value);
  });

// ✅ Simple fade-in and fade-out popup (no slide)
const popup = document.getElementById("popup");
if (popup) {
  // Appear instantly
  popup.style.opacity = "1";
  popup.style.transition = "opacity 0.5s ease";

  // Fade-out after 3 seconds
  setTimeout(() => {
    popup.style.opacity = "0";
    setTimeout(() => popup.remove(), 500); // Matches fade-out duration
  }, 2500);
}



});
