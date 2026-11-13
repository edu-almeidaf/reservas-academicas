document.addEventListener("DOMContentLoaded", function () {
  const select = document.getElementById("reservation_resource_id");
  const container = document.getElementById("reservation_subdivisions");

  if (!(select && container)) return;

  const list = container.querySelector("[data-subdivisions-list]");
  let selected = JSON.parse(container.dataset.selected || "[]");
  if (!Array.isArray(selected)) selected = [];

  async function loadSubdivisions(resourceId) {
    const url = container.dataset.urlTemplate.replace("__resource_id__", resourceId);
    const response = await fetch(url, { headers: { Accept: "application/json" } });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();
    return data.subdivisions;
  }

  function showMessage(message) {
    list.replaceChildren();
    const paragraph = document.createElement("p");
    paragraph.className = "text-muted mb-0";
    paragraph.textContent = message;
    list.appendChild(paragraph);
  }

  function renderSubdivisions(subdivisions) {
    if (subdivisions.length === 0) {
      showMessage("Este recurso não tem subdivisões: a reserva será do recurso inteiro.");
      return;
    }

    list.replaceChildren();
    subdivisions.forEach(function (subdivision) {
      const wrapper = document.createElement("div");
      wrapper.className = "form-check";

      const checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.className = "form-check-input";
      checkbox.name = "reservation[subdivision_ids][]";
      checkbox.value = subdivision.id;
      checkbox.id = `reservation_subdivision_${subdivision.id}`;
      checkbox.checked = selected.includes(subdivision.id);

      const label = document.createElement("label");
      label.className = "form-check-label";
      label.htmlFor = checkbox.id;
      label.textContent = subdivision.name;

      wrapper.append(checkbox, label);
      list.appendChild(wrapper);
    });
  }

  async function refresh() {
    if (!select.value) {
      list.replaceChildren();
      return;
    }

    showMessage("Carregando subdivisões...");

    try {
      renderSubdivisions(await loadSubdivisions(select.value));
    } catch (error) {
      showMessage("Não foi possível carregar as subdivisões.");
    }
  }

  select.addEventListener("change", function () {
    selected = [];
    refresh();
  });

  refresh();
});
