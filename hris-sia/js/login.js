function toggleForms() {
  const employeeForm = document.getElementById("employeeForm");
  const adminForm = document.getElementById("adminForm");

  employeeForm.classList.toggle("hidden");
  adminForm.classList.toggle("hidden");
}

function updateTime() {
  const timeElement = document.getElementById("currentTime");
  if (timeElement) {
    const now = new Date();
    const timeString = now.toLocaleTimeString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });
    timeElement.textContent = timeString;
  }
}

let clickCount = 0;
let clickTimeout;

document.getElementById("secretTrigger").addEventListener("click", function () {
  clickCount++;

  clearTimeout(clickTimeout);

  if (clickCount >= 5) {
    toggleForms();
    clickCount = 0;
  }

  clickTimeout = setTimeout(() => {
    clickCount = 0;
  }, 2000);
});

document.addEventListener("keydown", function (e) {
  if (e.ctrlKey && e.shiftKey && e.key === "A") {
    e.preventDefault();
    toggleForms();
  }
});

updateTime();
setInterval(updateTime, 1000);

window.addEventListener("DOMContentLoaded", function () {
  const employeeInput = document.getElementById("employee_number");
  if (
    employeeInput &&
    !document.getElementById("employeeForm").classList.contains("hidden")
  ) {
    employeeInput.focus();
  }
});
