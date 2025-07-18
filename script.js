const contactForm = document.getElementById("contactForm")

// Validasi
function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

function validateField(field) {
  const value = field.value.trim()
  const fieldName = field.name
  let isValid = true
  let errorMessage = ""

  if (field.hasAttribute("required") && !value) {
    isValid = false
    errorMessage = `${getFieldLabel(fieldName)} wajib diisi`
  } else if (fieldName === "email" && value && !validateEmail(value)) {
    isValid = false
    errorMessage = "Format email tidak valid"
  } else if (fieldName === "name" && value.length < 2) {
    isValid = false
    errorMessage = "Nama minimal 2 karakter"
  } else if (fieldName === "subject" && value.length < 5) {
    isValid = false
    errorMessage = "Subject minimal 5 karakter"
  } else if (fieldName === "message" && value.length < 10) {
    isValid = false
    errorMessage = "Pesan minimal 10 karakter"
  }

  showFieldError(field, errorMessage)
  return isValid
}

function getFieldLabel(fieldName) {
  const labels = {
    name: "Nama",
    email: "Email",
    phone: "No. Telepon",
    subject: "Subject",
    message: "Pesan",
  }
  return labels[fieldName] || fieldName
}

function showFieldError(field, errorMessage) {
  const errorElement = document.getElementById(`${field.name}Error`)
  const formGroup = field.closest(".form-group")

  if (errorMessage) {
    if (errorElement) errorElement.textContent = errorMessage
    if (formGroup) {
      formGroup.classList.add("error")
      formGroup.classList.remove("success")
    }
  } else {
    if (errorElement) errorElement.textContent = ""
    if (formGroup) {
      formGroup.classList.remove("error")
      if (field.value.trim()) {
        formGroup.classList.add("success")
      }
    }
  }
}

function showNotification(message, type = "info") {
  const existingNotifications = document.querySelectorAll(".notification")
  existingNotifications.forEach((n) => n.remove())

  const notification = document.createElement("div")
  notification.className = `notification notification-${type}`
  notification.innerHTML = `
    <div class="notification-content">
      <span class="notification-message">${message}</span>
      <button class="notification-close">&times;</button>
    </div>
  `
  notification.style.cssText = `
    position: fixed;
    top: 100px;
    right: 20px;
    background: ${type === "success" ? "#28a745" : type === "error" ? "#dc3545" : "#667eea"};
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    z-index: 10000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    max-width: 400px;
  `
  document.body.appendChild(notification)
  setTimeout(() => {
    notification.style.transform = "translateX(0)"
  }, 100)

  notification.querySelector(".notification-close").addEventListener("click", () => {
    notification.remove()
  })

  setTimeout(() => {
    notification.remove()
  }, 5000)
}

// Kirim data ke process_contact.php
if (contactForm) {
  contactForm.addEventListener("submit", (e) => {
    e.preventDefault()

    const fields = ["name", "email", "phone", "subject", "message"]
    let isValid = true

    fields.forEach((name) => {
      const field = document.getElementById(name)
      if (!validateField(field)) {
        isValid = false
      }
    })

    if (!isValid) {
      showNotification("Mohon perbaiki kesalahan pada form", "error")
      return
    }

    const formData = new FormData(contactForm)
    const submitBtn = contactForm.querySelector('button[type="submit"]')
    const originalText = submitBtn.textContent
    submitBtn.textContent = "Mengirim..."
    submitBtn.disabled = true

    fetch("process_contact.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) throw new Error("Gagal mengirim pesan")
        return response.text()
      })
      .then(() => {
        showNotification("Pesan berhasil dikirim!", "success")
        contactForm.reset()
        document.querySelectorAll(".form-group").forEach((group) => {
          group.classList.remove("success", "error")
        })
      })
      .catch(() => {
        showNotification("Terjadi kesalahan saat mengirim pesan", "error")
      })
      .finally(() => {
        submitBtn.textContent = originalText
        submitBtn.disabled = false
      })
  })
}

// Animasikan skill bar saat halaman dimuat
document.addEventListener("DOMContentLoaded", () => {
  const skillBars = document.querySelectorAll(".skill-progress");
  skillBars.forEach((bar) => {
    const width = bar.getAttribute("data-width");
    bar.style.width = width;
  });
});

// Toggle menu saat hamburger diklik
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });
}

// Highlight link saat diklik
const navLinks = document.querySelectorAll('.nav-link');

navLinks.forEach(link => {
    link.addEventListener('click', () => {
        navLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
    });
});

