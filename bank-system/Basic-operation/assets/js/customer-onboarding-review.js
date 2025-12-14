/**
 * Customer Onboarding Review Page
 * Step 3: Review and Submit
 */

// Detect API path dynamically based on current page location
function getApiBaseUrl() {
  // Get the current page path
  const currentPath = window.location.pathname;

  // If we're in /public/, go up one level to find /api/
  if (currentPath.includes("/public/")) {
    const basePath = currentPath.substring(0, currentPath.indexOf("/public/"));
    return window.location.origin + basePath + "/api";
  }

  // Fallback: construct from known structure
  const pathParts = currentPath.split("/");
  const basicOpIndex = pathParts.indexOf("Basic-operation");
  if (basicOpIndex !== -1) {
    const basePath = pathParts.slice(0, basicOpIndex + 1).join("/");
    return window.location.origin + basePath + "/api";
  }

  // Final fallback
  return window.location.origin + "/Evergreen/bank-system/Basic-operation/api";
}

const API_BASE_URL = getApiBaseUrl();
console.log("API Base URL:", API_BASE_URL);
let sessionData = null;
let editMode = {};
let originalValues = {};
let isLoadingData = false; // Prevent multiple simultaneous data loads

/**
 * Initialize page on load
 */
document.addEventListener("DOMContentLoaded", function () {
  loadSessionData();
  setupEventListeners();
});

/**
 * Setup event listeners
 */
function setupEventListeners() {
  // Terms checkbox
  const termsCheckbox = document.getElementById("terms-checkbox");
  if (termsCheckbox) {
    termsCheckbox.addEventListener("change", function () {
      clearTermsError();
    });
  }

  // Edit buttons for each section
  const editButtons = document.querySelectorAll(".btn-edit-icon[data-section]");
  console.log("Found", editButtons.length, "edit buttons");

  editButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      const sectionId = this.getAttribute("data-section");
      console.log("Edit button clicked for section:", sectionId);

      if (sectionId) {
        enableEditMode(
          sectionId,
          document.querySelector(`#section-${sectionId}`)
        );
      } else {
        console.error("Could not find section ID");
      }
    });
  });

  // Back button
  const backBtn = document.getElementById("back-btn");
  if (backBtn) {
    backBtn.addEventListener("click", goBack);
  }

  // Submit button
  const submitBtn = document.getElementById("submit-btn");
  if (submitBtn) {
    submitBtn.addEventListener("click", submitApplication);
  }
}

/**
 * Load session data from sessionStorage
 */
async function loadSessionData() {
  // Prevent multiple simultaneous calls
  if (isLoadingData) {
    console.log("Already loading session data, skipping...");
    return;
  }

  isLoadingData = true;

  try {
    // Get data from sessionStorage instead of API
    const step1Data = sessionStorage.getItem("onboarding_step1");
    const step2Data = sessionStorage.getItem("onboarding_step2");

    console.log("Step 1 data from sessionStorage:", step1Data);
    console.log("Step 2 data from sessionStorage:", step2Data);

    if (!step1Data) {
      console.error("No step 1 data found in sessionStorage");
      showGlobalError("Session expired. Please start from the beginning.");
      setTimeout(() => {
        window.location.href = "customer-onboarding-details.html";
      }, 2000);
      isLoadingData = false;
      return;
    }

    // Parse the data
    const parsedStep1 = JSON.parse(step1Data);
    const parsedStep2 = step2Data ? JSON.parse(step2Data) : {};

    // Combine step 1 and step 2 data
    const combinedData = {
      ...parsedStep1,
      ...parsedStep2,
    };

    console.log("Combined session data:", combinedData);

    // Check if we have minimum required data from step 1
    if (!combinedData.first_name || !combinedData.last_name) {
      console.error("Missing required step 1 data");
      showGlobalError(
        "Missing required information. Please start from the beginning."
      );
      setTimeout(() => {
        window.location.href = "customer-onboarding-details.html";
      }, 2000);
      isLoadingData = false;
      return;
    }

    sessionData = combinedData;
    populateReviewData(sessionData);
    isLoadingData = false;
  } catch (error) {
    isLoadingData = false;
    console.error("Error loading session data:", error);
    showGlobalError("Error loading your information. Please try again.");
    setTimeout(() => {
      window.location.href = "customer-onboarding-details.html";
    }, 2000);
  }
}

/**
 * Populate review fields with session data
 */
function populateReviewData(data) {
  console.log("Populating review with data:", data);

  try {
    // Personal Information - Name fields
    setFieldValue("review-first-name", data.first_name || "");
    setFieldValue("review-middle-name", data.middle_name || "");
    setFieldValue("review-last-name", data.last_name || "");

    // Map field names from Step 1 form to review display
    setFieldValue(
      "review-birth-date",
      formatDate(data.date_of_birth || data.birth_date)
    );
    setFieldValue(
      "review-birth-place",
      data.place_of_birth || data.birth_place
    );
    setFieldValue("review-gender", data.gender);
    setFieldValue(
      "review-civil-status",
      data.marital_status || data.civil_status
    );
    setFieldValue("review-nationality", data.nationality);

    // Address fields - now using IDs from dropdowns
    setFieldValue("review-address", data.address_line || data.street);

    // Fetch city, province, and barangay names from IDs
    fetchLocationNames(data.city_id, data.province_id, data.barangay_id);

    setFieldValue("review-postal-code", data.postal_code); // Contact Information - handle arrays from Step 1
    const email =
      Array.isArray(data.emails) && data.emails.length > 0
        ? data.emails[0]
        : data.email || data.verified_email || data.email_verification || "";
    const mobile =
      data.mobile_number ||
      (Array.isArray(data.phones) && data.phones.length > 0
        ? data.phones[0]
        : "");

    // Email is shown in both Contact Details and Account Security sections
    setFieldValue("review-email", email);
    // Only set mobile if it exists (might not exist if email verification was used)
    if (mobile) {
      setFieldValue("review-mobile", formatPhoneNumber(mobile));
    } else {
      setFieldValue("review-mobile", "N/A");
    }

    // Employment Information - now using IDs from dropdowns
    fetchEmploymentAndFundsNames(data.employment_status, data.source_of_funds);

    const employer = data.employer_name || "";
    setFieldValue("review-employer", employer);

    // Account Type
    const accountType = data.account_type || "Savings";
    setFieldValue("review-account-type", accountType + " Account");

    // Document Verification - ID Type and ID Number from step 2
    setFieldValue("review-id-type", data.id_type || "-");
    setFieldValue("review-id-number", data.id_number || "-");

    // Documents Uploaded status
    if (data.documents_uploaded) {
      setFieldValue("review-documents", "ID Front & Back");
    } else {
      setFieldValue("review-documents", "Not uploaded");
    }

    // Note: Email is already set above and will be shown in Account Security section as username
  } catch (error) {
    console.error("Error populating review data:", error);
    showGlobalError(
      "Error displaying your information. Some fields may be missing."
    );
  }
}

/**
 * Fetch location names from IDs
 */
async function fetchLocationNames(cityId, provinceId, barangayId) {
  try {
    const API_BASE_URL = getApiBaseUrl();

    // Fetch province name
    if (provinceId) {
      const provinceResponse = await fetch(
        `${API_BASE_URL}/location/get-provinces.php`
      );
      const provinceResult = await provinceResponse.json();
      if (provinceResult.success && provinceResult.data) {
        const province = provinceResult.data.find(
          (p) => p.province_id == provinceId
        );
        if (province) {
          setFieldValue("review-province", province.province_name);
        }
      }
    }

    // Fetch city name
    if (cityId) {
      const cityResponse = await fetch(
        `${API_BASE_URL}/location/get-cities.php?province_id=${provinceId}`
      );
      const cityResult = await cityResponse.json();
      if (cityResult.success && cityResult.data) {
        const city = cityResult.data.find((c) => c.city_id == cityId);
        if (city) {
          setFieldValue("review-city", city.city_name);
        }
      }
    }

    // Fetch barangay name
    if (barangayId && cityId) {
      const barangayResponse = await fetch(
        `${API_BASE_URL}/location/get-barangays.php?city_id=${cityId}`
      );
      const barangayResult = await barangayResponse.json();
      if (barangayResult.success && barangayResult.data) {
        const barangay = barangayResult.data.find(
          (b) => b.barangay_id == barangayId
        );
        if (barangay) {
          setFieldValue("review-barangay", barangay.barangay_name);
        }
      }
    }
  } catch (error) {
    console.error("Error fetching location names:", error);
  }
}

/**
 * Fetch employment status and source of funds names from IDs
 */
async function fetchEmploymentAndFundsNames(
  employmentStatusId,
  sourceOfFundsId
) {
  try {
    const API_BASE_URL = getApiBaseUrl();

    // Fetch employment status name
    if (employmentStatusId) {
      const employmentResponse = await fetch(
        `${API_BASE_URL}/common/get-employment-statuses.php`
      );
      const employmentResult = await employmentResponse.json();
      if (employmentResult.success && employmentResult.data) {
        const employment = employmentResult.data.find(
          (e) => e.employment_status_id == employmentStatusId
        );
        if (employment) {
          setFieldValue("review-occupation", employment.status_name);
        }
      }
    }

    // Fetch source of funds name
    if (sourceOfFundsId) {
      const fundsResponse = await fetch(
        `${API_BASE_URL}/common/get-source-of-funds.php`
      );
      const fundsResult = await fundsResponse.json();
      if (fundsResult.success && fundsResult.data) {
        const funds = fundsResult.data.find(
          (f) => f.source_id == sourceOfFundsId
        );
        if (funds) {
          setFieldValue("review-annual-income", funds.source_name);
        }
      }
    }
  } catch (error) {
    console.error("Error fetching employment/funds names:", error);
  }
}

/**
 * Set field value with fallback
 */
function setFieldValue(elementId, value) {
  const element = document.getElementById(elementId);
  if (element) {
    element.textContent = value || "Not provided";
  }
}

/**
 * Format date for display
 */
function formatDate(dateString) {
  if (!dateString) return "Not provided";

  const date = new Date(dateString);
  const options = { year: "numeric", month: "long", day: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

/**
 * Format phone number for display
 */
function formatPhoneNumber(phoneNumber) {
  if (!phoneNumber) return "Not provided";

  // Convert to string if it's not already (handle objects/arrays)
  let phoneStr = phoneNumber;
  if (typeof phoneNumber === "object") {
    // If it's an object with a phone property, use that
    if (phoneNumber.phone) {
      phoneStr = phoneNumber.phone;
    } else if (phoneNumber.number) {
      phoneStr = phoneNumber.number;
    } else {
      // Convert object to string as fallback
      phoneStr = JSON.stringify(phoneNumber);
    }
  } else if (typeof phoneNumber !== "string") {
    phoneStr = String(phoneNumber);
  }

  // If it starts with +, format it nicely
  if (phoneStr.startsWith("+")) {
    // Format as +XX XXX XXX XXXX
    const cleaned = phoneStr.replace(/\D/g, "");
    if (cleaned.length >= 10) {
      return `+${cleaned.slice(0, 2)} ${cleaned.slice(2, 5)} ${cleaned.slice(
        5,
        8
      )} ${cleaned.slice(8)}`;
    }
  }

  return phoneStr;
}

/**
 * Format currency for display
 */
function formatCurrency(amount) {
  if (!amount) return "Not provided";

  // If it's already a string description (like "Employment"), return as is
  if (isNaN(amount)) {
    return amount;
  }

  // Convert to number if string
  const numAmount = typeof amount === "string" ? parseFloat(amount) : amount;

  if (isNaN(numAmount)) return amount;

  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(numAmount);
}

/**
 * Edit a specific section
 */
function editSection(sectionId) {
  const section = document.querySelector(`#section-${sectionId}`);
  if (!section) return;

  if (editMode[sectionId]) {
    // Save mode - save the changes
    saveSection(sectionId);
  } else {
    // Edit mode - make fields editable
    enableEditMode(sectionId, section);
  }
}

/**
 * Enable edit mode for a section
 */
function enableEditMode(sectionId, section) {
  editMode[sectionId] = true;
  originalValues[sectionId] = {};

  const valueElements = section.querySelectorAll(".value");
  valueElements.forEach((el) => {
    const id = el.id;

    // For security section, always require password reentry
    if (sectionId === "account-security" && id === "review-password") {
      originalValues[sectionId][id] = "••••••••";
      el.innerHTML = `
        <div class="password-edit-container">
          <input type="password" class="form-control form-control-sm mb-2" id="edit-new-password" placeholder="Enter Password" required>
          <input type="password" class="form-control form-control-sm" id="edit-confirm-password" placeholder="Confirm Password" required>
        </div>
      `;
      return;
    }

    // For username in security section, create regular input
    if (sectionId === "account-security" && id === "review-username") {
      originalValues[sectionId][id] = el.textContent;
      const currentValue =
        el.textContent === "Not provided" ? "" : el.textContent;

      const inputElement = document.createElement("input");
      inputElement.type = "text";
      inputElement.value = currentValue;
      inputElement.className = "form-control form-control-sm";
      inputElement.id = `edit-${id}`;
      inputElement.style.width = "100%";

      el.innerHTML = "";
      el.appendChild(inputElement);
      return;
    }

    // Skip password display field in non-security sections
    if (el.textContent.includes("••••")) {
      return;
    }

    // For all other fields
    originalValues[sectionId][id] = el.textContent;

    const currentValue =
      el.textContent === "Not provided" ? "" : el.textContent;

    // Create appropriate input based on field type
    let inputElement;

    // Check if this field should be a dropdown
    if (id === "review-gender") {
      // Gender dropdown
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.innerHTML = `
          <option value="">Select Gender</option>
          <option value="Male" ${
            currentValue === "Male" ? "selected" : ""
          }>Male</option>
          <option value="Female" ${
            currentValue === "Female" ? "selected" : ""
          }>Female</option>
          <option value="Other" ${
            currentValue === "Other" ? "selected" : ""
          }>Other</option>
        `;
    } else if (id === "review-civil-status") {
      // Civil Status dropdown
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      const lowerCurrentValue = currentValue.toLowerCase();
      inputElement.innerHTML = `
          <option value="">Select Civil Status</option>
          <option value="single" ${
            lowerCurrentValue === "single" ? "selected" : ""
          }>Single</option>
          <option value="married" ${
            lowerCurrentValue === "married" ? "selected" : ""
          }>Married</option>
          <option value="widowed" ${
            lowerCurrentValue === "widowed" ? "selected" : ""
          }>Widowed</option>
          <option value="divorced" ${
            lowerCurrentValue === "divorced" ? "selected" : ""
          }>Divorced</option>
        `;
    } else if (id === "review-nationality") {
      // Nationality dropdown
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.innerHTML = `
          <option value="">Select Nationality</option>
          <option value="Filipino" ${
            currentValue === "Filipino" ? "selected" : ""
          }>Filipino</option>
          <option value="American" ${
            currentValue === "American" ? "selected" : ""
          }>American</option>
          <option value="Chinese" ${
            currentValue === "Chinese" ? "selected" : ""
          }>Chinese</option>
          <option value="Japanese" ${
            currentValue === "Japanese" ? "selected" : ""
          }>Japanese</option>
          <option value="Korean" ${
            currentValue === "Korean" ? "selected" : ""
          }>Korean</option>
          <option value="Other" ${
            currentValue.includes("Other") ||
            (![
              "Filipino",
              "American",
              "Chinese",
              "Japanese",
              "Korean",
            ].includes(currentValue) &&
              currentValue)
              ? "selected"
              : ""
          }>Other</option>
        `;
    } else if (id === "review-occupation") {
      // Occupation/Employment Status dropdown - fetch from API
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.id = `edit-${id}`;
      inputElement.style.width = "100%";
      inputElement.innerHTML = '<option value="">Loading...</option>';

      // Fetch employment statuses from API
      fetch(`${API_BASE_URL}/common/get-employment-statuses.php`)
        .then((response) => response.json())
        .then((result) => {
          if (result.success && result.data) {
            inputElement.innerHTML =
              '<option value="">Select Employment Status</option>';
            result.data.forEach((status) => {
              const option = document.createElement("option");
              option.value = status.employment_status_id;
              option.textContent = status.status_name;
              if (status.status_name === currentValue) {
                option.selected = true;
              }
              inputElement.appendChild(option);
            });
          }
        })
        .catch((error) =>
          console.error("Error loading employment statuses:", error)
        );
    } else if (id === "review-annual-income") {
      // Source of Funds dropdown - fetch from API
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.id = `edit-${id}`;
      inputElement.style.width = "100%";
      inputElement.innerHTML = '<option value="">Loading...</option>';

      // Fetch source of funds from API
      fetch(`${API_BASE_URL}/common/get-source-of-funds.php`)
        .then((response) => response.json())
        .then((result) => {
          if (result.success && result.data) {
            inputElement.innerHTML =
              '<option value="">Select Source of Funds</option>';
            result.data.forEach((source) => {
              const option = document.createElement("option");
              option.value = source.source_id;
              option.textContent = source.source_name;
              if (source.source_name === currentValue) {
                option.selected = true;
              }
              inputElement.appendChild(option);
            });
          }
        })
        .catch((error) =>
          console.error("Error loading source of funds:", error)
        );
    } else if (id === "review-account-type") {
      // Account Type dropdown
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      const currentAccountType = currentValue.replace(" Account", "");
      inputElement.innerHTML = `
          <option value="">Select Account Type</option>
          <option value="Savings" ${
            currentAccountType === "Savings" ? "selected" : ""
          }>Savings Account</option>
          <option value="Checking" ${
            currentAccountType === "Checking" ? "selected" : ""
          }>Checking Account</option>
        `;
    } else if (id === "review-province") {
      // Province dropdown - fetch from API
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.id = `edit-${id}`;
      inputElement.style.width = "100%";
      inputElement.innerHTML = '<option value="">Loading...</option>';

      // Fetch provinces from API
      fetch(`${API_BASE_URL}/location/get-provinces.php`)
        .then((response) => response.json())
        .then((result) => {
          if (result.success && result.data) {
            inputElement.innerHTML =
              '<option value="">Select Province</option>';
            result.data.forEach((province) => {
              const option = document.createElement("option");
              option.value = province.province_id;
              option.textContent = province.province_name;
              if (province.province_name === currentValue) {
                option.selected = true;
              }
              inputElement.appendChild(option);
            });

            // Add change event listener to update city dropdown
            inputElement.addEventListener("change", function () {
              const selectedProvinceId = this.value;
              const cityDropdown = document.getElementById("edit-review-city");
              const barangayDropdown = document.getElementById(
                "edit-review-barangay"
              );

              if (cityDropdown) {
                if (selectedProvinceId) {
                  cityDropdown.innerHTML =
                    '<option value="">Loading...</option>';
                  cityDropdown.disabled = false;

                  fetch(
                    `${API_BASE_URL}/location/get-cities.php?province_id=${selectedProvinceId}`
                  )
                    .then((response) => response.json())
                    .then((result) => {
                      if (result.success && result.data) {
                        cityDropdown.innerHTML =
                          '<option value="">Select City</option>';
                        result.data.forEach((city) => {
                          const option = document.createElement("option");
                          option.value = city.city_id;
                          option.textContent = city.city_name;
                          cityDropdown.appendChild(option);
                        });
                      }
                    })
                    .catch((error) =>
                      console.error("Error loading cities:", error)
                    );
                } else {
                  cityDropdown.innerHTML =
                    '<option value="">Select Province First</option>';
                  cityDropdown.disabled = true;
                }
              }

              // Reset barangay dropdown
              if (barangayDropdown) {
                barangayDropdown.innerHTML =
                  '<option value="">Select City First</option>';
                barangayDropdown.disabled = true;
              }
            });
          }
        })
        .catch((error) => console.error("Error loading provinces:", error));
    } else if (id === "review-city") {
      // City dropdown - fetch from API
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.id = `edit-${id}`;
      inputElement.style.width = "100%";
      inputElement.innerHTML = '<option value="">Loading...</option>';

      // Get province ID from session data or from province dropdown if it exists
      const provinceDropdown = document.getElementById("edit-review-province");
      const provinceId = provinceDropdown
        ? provinceDropdown.value
        : sessionData.province_id;

      if (provinceId) {
        fetch(
          `${API_BASE_URL}/location/get-cities.php?province_id=${provinceId}`
        )
          .then((response) => response.json())
          .then((result) => {
            if (result.success && result.data) {
              inputElement.innerHTML = '<option value="">Select City</option>';
              result.data.forEach((city) => {
                const option = document.createElement("option");
                option.value = city.city_id;
                option.textContent = city.city_name;
                if (city.city_name === currentValue) {
                  option.selected = true;
                }
                inputElement.appendChild(option);
              });

              // Add change event listener to update barangay dropdown
              inputElement.addEventListener("change", function () {
                const selectedCityId = this.value;
                const barangayDropdown = document.getElementById(
                  "edit-review-barangay"
                );

                if (barangayDropdown) {
                  if (selectedCityId) {
                    barangayDropdown.innerHTML =
                      '<option value="">Loading...</option>';
                    barangayDropdown.disabled = false;

                    fetch(
                      `${API_BASE_URL}/location/get-barangays.php?city_id=${selectedCityId}`
                    )
                      .then((response) => response.json())
                      .then((result) => {
                        if (result.success && result.data) {
                          barangayDropdown.innerHTML =
                            '<option value="">Select Barangay</option>';
                          result.data.forEach((barangay) => {
                            const option = document.createElement("option");
                            option.value = barangay.barangay_id;
                            option.textContent = barangay.barangay_name;
                            barangayDropdown.appendChild(option);
                          });
                        }
                      })
                      .catch((error) =>
                        console.error("Error loading barangays:", error)
                      );
                  } else {
                    barangayDropdown.innerHTML =
                      '<option value="">Select City First</option>';
                    barangayDropdown.disabled = true;
                  }
                }
              });
            }
          })
          .catch((error) => console.error("Error loading cities:", error));
      } else {
        inputElement.innerHTML =
          '<option value="">Select Province First</option>';
        inputElement.disabled = true;
      }
    } else if (id === "review-barangay") {
      // Barangay dropdown - fetch from API
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.id = `edit-${id}`;
      inputElement.style.width = "100%";
      inputElement.innerHTML = '<option value="">Loading...</option>';

      // Get city ID from session data or from city dropdown if it exists
      const cityDropdown = document.getElementById("edit-review-city");
      const cityId = cityDropdown ? cityDropdown.value : sessionData.city_id;

      if (cityId) {
        fetch(`${API_BASE_URL}/location/get-barangays.php?city_id=${cityId}`)
          .then((response) => response.json())
          .then((result) => {
            if (result.success && result.data) {
              inputElement.innerHTML =
                '<option value="">Select Barangay</option>';
              result.data.forEach((barangay) => {
                const option = document.createElement("option");
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name;
                if (barangay.barangay_name === currentValue) {
                  option.selected = true;
                }
                inputElement.appendChild(option);
              });
            }
          })
          .catch((error) => console.error("Error loading barangays:", error));
      } else {
        inputElement.innerHTML = '<option value="">Select City First</option>';
        inputElement.disabled = true;
      }
    } else if (id === "review-birth-place") {
      // Place of Birth dropdown - fetch all cities
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.id = `edit-${id}`;
      inputElement.style.width = "100%";
      inputElement.innerHTML = '<option value="">Loading...</option>';

      // Fetch all cities for birth place
      fetch(`${API_BASE_URL}/location/get-all-cities.php`)
        .then((response) => response.json())
        .then((result) => {
          if (result.success && result.data) {
            inputElement.innerHTML = '<option value="">Select City</option>';
            result.data.forEach((city) => {
              const option = document.createElement("option");
              option.value = city.city_name;
              option.textContent = city.city_name;
              if (city.city_name === currentValue) {
                option.selected = true;
              }
              inputElement.appendChild(option);
            });
          }
        })
        .catch((error) =>
          console.error("Error loading cities for birth place:", error)
        );
    } else if (id === "review-birth-date") {
      // Date input
      inputElement = document.createElement("input");
      inputElement.type = "date";
      inputElement.className = "form-control form-control-sm";
      // Try to parse the date
      if (currentValue) {
        const date = new Date(currentValue);
        if (!isNaN(date)) {
          inputElement.value = date.toISOString().split("T")[0];
        }
      }
    } else {
      // Regular text input
      inputElement = document.createElement("input");
      inputElement.type = "text";
      inputElement.value = currentValue;
      inputElement.className = "form-control form-control-sm";
    }

    inputElement.id = `edit-${id}`;
    inputElement.style.width = "100%";

    // Replace text with input/select
    el.innerHTML = "";
    el.appendChild(inputElement);
  });

  // Update buttons - both Save and Cancel in header
  const btnContainer = section.querySelector(".section-header-inline");
  const editBtn = btnContainer.querySelector(".btn-edit-icon");

  if (editBtn) {
    // Hide the edit button and create new Save/Cancel buttons
    editBtn.style.display = "none";
  }

  // Create button group container
  const buttonGroup = document.createElement("div");
  buttonGroup.className = "edit-button-group";
  buttonGroup.style.display = "flex";
  buttonGroup.style.gap = "0.5rem";

  // Create Save button
  const saveBtn = document.createElement("button");
  saveBtn.type = "button";
  saveBtn.className = "btn-edit-icon btn-save-edit";
  saveBtn.innerHTML = "<span>Save</span>";
  saveBtn.style.backgroundColor = "#003631";
  saveBtn.style.color = "white";
  saveBtn.style.border = "none";
  saveBtn.onclick = () => saveSection(sectionId);

  // Create Cancel button
  const cancelBtn = document.createElement("button");
  cancelBtn.type = "button";
  cancelBtn.className = "btn-edit-icon btn-cancel-edit";
  cancelBtn.innerHTML = "<span>Cancel</span>";
  cancelBtn.style.backgroundColor = "#6c757d";
  cancelBtn.style.color = "white";
  cancelBtn.style.border = "none";
  cancelBtn.onclick = () => cancelEdit(sectionId);

  buttonGroup.appendChild(saveBtn);
  buttonGroup.appendChild(cancelBtn);
  btnContainer.appendChild(buttonGroup);
}

/**
 * Save section changes
 */
async function saveSection(sectionId) {
  const section = document.querySelector(`#section-${sectionId}`);
  const updatedData = {};
  let hasError = false;

  // For security section, validate password fields
  if (sectionId === "account-security") {
    const newPassword = document.getElementById("edit-new-password");
    const confirmPassword = document.getElementById("edit-confirm-password");

    if (
      !newPassword ||
      !confirmPassword ||
      !newPassword.value ||
      !confirmPassword.value
    ) {
      showSuccessMessage(
        "Please enter both password fields!",
        "error",
        section
      );
      return;
    }

    if (newPassword.value !== confirmPassword.value) {
      showSuccessMessage("Passwords do not match!", "error", section);
      return;
    }

    // Validate password requirements
    const password = newPassword.value;
    const errors = [];

    if (password.length < 8) {
      errors.push("at least 8 characters");
    }
    if (!/[A-Z]/.test(password)) {
      errors.push("one uppercase letter");
    }
    if (!/[a-z]/.test(password)) {
      errors.push("one lowercase letter");
    }
    if (!/[0-9]/.test(password)) {
      errors.push("one number");
    }
    if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
      errors.push("one special character");
    }

    if (errors.length > 0) {
      showSuccessMessage(
        "Password must contain " + errors.join(", ") + "!",
        "error",
        section
      );
      return;
    }

    // Hash password and update
    updatedData.password = newPassword.value;
    updatedData.confirm_password = confirmPassword.value;
  }

  // Collect new values from inputs and selects
  const inputs = section.querySelectorAll(
    "input.form-control, input.form-control-sm, select.form-select, select.form-select-sm"
  );
  inputs.forEach((input) => {
    // Skip password fields as they're handled separately
    if (
      input.id === "edit-new-password" ||
      input.id === "edit-confirm-password"
    ) {
      return;
    }

    const fieldId = input.id.replace("edit-", "");
    let newValue = input.value.trim();

    // Validate required fields (all fields except middle name and employer name)
    const optionalFields = ["review-middle-name", "review-employer"];
    const isRequired = !optionalFields.includes(fieldId);

    if (
      isRequired &&
      (!newValue || (input.tagName === "SELECT" && newValue === ""))
    ) {
      hasError = true;
      // Add visual feedback
      input.classList.add("is-invalid");
      // Remove invalid class on change
      input.addEventListener(
        "input",
        function () {
          this.classList.remove("is-invalid");
        },
        { once: true }
      );
    } else {
      input.classList.remove("is-invalid");
    }

    // For date inputs, format nicely
    if (input.type === "date" && newValue) {
      const date = new Date(newValue);
      newValue = date.toISOString().split("T")[0]; // Keep YYYY-MM-DD format for storage
    }

    // Map field IDs to session data keys
    const fieldMap = {
      "review-first-name": "first_name",
      "review-middle-name": "middle_name",
      "review-last-name": "last_name",
      "review-birth-date": "date_of_birth",
      "review-birth-place": "place_of_birth",
      "review-gender": "gender",
      "review-civil-status": "marital_status",
      "review-nationality": "nationality",
      "review-address": "address_line",
      "review-city": "city_id",
      "review-province": "province_id",
      "review-barangay": "barangay_id",
      "review-postal-code": "postal_code",
      "review-email": "email",
      "review-mobile": "mobile_number",
      "review-occupation": "employment_status",
      "review-employer": "employer_name",
      "review-annual-income": "source_of_funds",
      "review-account-type": "account_type",
      "review-username": "username",
      "review-id-type": "id_type",
      "review-id-number": "id_number",
    };

    const dataKey = fieldMap[fieldId];
    if (dataKey && newValue) {
      updatedData[dataKey] = newValue;
    }
  });

  // If there are validation errors, show message and stop
  if (hasError) {
    showSuccessMessage("Please fill in all required fields!", "error", section);
    return;
  }

  try {
    // Update sessionStorage directly instead of API call
    const step1Data = sessionStorage.getItem("onboarding_step1");
    const step2Data = sessionStorage.getItem("onboarding_step2");

    let parsedStep1 = step1Data ? JSON.parse(step1Data) : {};
    let parsedStep2 = step2Data ? JSON.parse(step2Data) : {};

    // Get current data based on section
    let currentData = {};
    if (
      sectionId === "personal-details" ||
      sectionId === "contact-details" ||
      sectionId === "financial-details"
    ) {
      currentData = parsedStep1;
    } else if (sectionId === "document-verification") {
      currentData = parsedStep2;
    }

    // Check if any changes were actually made
    let hasChanges = false;
    for (const key in updatedData) {
      if (updatedData[key] !== currentData[key]) {
        hasChanges = true;
        break;
      }
    }

    // If no changes, show info message and exit edit mode
    if (!hasChanges) {
      disableEditMode(sectionId, section);
      showSuccessMessage("No changes were made.", "info", section);
      return;
    }

    // Merge updated data into the appropriate step
    // Personal, Contact, and Financial details go to step1
    if (
      sectionId === "personal-details" ||
      sectionId === "contact-details" ||
      sectionId === "financial-details"
    ) {
      parsedStep1 = { ...parsedStep1, ...updatedData };
      sessionStorage.setItem("onboarding_step1", JSON.stringify(parsedStep1));
    }
    // Document verification goes to step2
    else if (sectionId === "document-verification") {
      parsedStep2 = { ...parsedStep2, ...updatedData };
      sessionStorage.setItem("onboarding_step2", JSON.stringify(parsedStep2));
    }

    // Update the global sessionData
    sessionData = { ...parsedStep1, ...parsedStep2 };

    // Exit edit mode
    disableEditMode(sectionId, section);

    // Show success message
    showSuccessMessage("Changes saved successfully!", "success", section);

    // Refresh the display with updated data
    setTimeout(() => {
      populateReviewData(sessionData);
      // Remove success message after refresh
      const existingMsg = section.querySelector(".edit-message");
      if (existingMsg) {
        existingMsg.remove();
      }
    }, 1000);
  } catch (error) {
    console.error("Error saving changes:", error);
    showSuccessMessage(
      "Error saving changes. Please try again.",
      "error",
      section
    );
  }
}

/**
 * Show success/error/info message inline
 */
function showSuccessMessage(message, type = "success", section) {
  // Remove any existing message
  const existingMsg = section.querySelector(".edit-message");
  if (existingMsg) {
    existingMsg.remove();
  }

  // Map type to Bootstrap alert class
  let alertClass = "success";
  if (type === "error") {
    alertClass = "danger";
  } else if (type === "info") {
    alertClass = "info";
  }

  // Create message element
  const msgEl = document.createElement("div");
  msgEl.className = `edit-message alert alert-${alertClass} mt-2`;
  msgEl.style.padding = "0.5rem 1rem";
  msgEl.style.fontSize = "0.9rem";
  msgEl.textContent = message;

  // Insert after section header
  const header = section.querySelector(".section-header-inline");
  header.parentNode.insertBefore(msgEl, header.nextSibling);

  // Auto-remove after 3 seconds for success/info messages
  if (type === "success" || type === "info") {
    setTimeout(() => {
      msgEl.remove();
    }, 3000);
  }
}

/**
 * Cancel edit mode
 */
function cancelEdit(sectionId) {
  const section = document.querySelector(`#section-${sectionId}`);

  // Restore original values by removing input elements and restoring text
  Object.keys(originalValues[sectionId] || {}).forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      // Remove all child elements (inputs/selects)
      while (el.firstChild) {
        el.removeChild(el.firstChild);
      }
      // Restore the original text content
      el.textContent = originalValues[sectionId][id];
    }
  });

  disableEditMode(sectionId, section);
}

/**
 * Disable edit mode
 */
function disableEditMode(sectionId, section) {
  editMode[sectionId] = false;

  // Show the original edit button again
  const editBtn = section.querySelector(
    ".btn-edit-icon:not(.btn-save-edit):not(.btn-cancel-edit)"
  );
  if (editBtn) {
    editBtn.style.display = "";
  }

  // Remove button group
  const buttonGroup = section.querySelector(".edit-button-group");
  if (buttonGroup) {
    buttonGroup.remove();
  }

  // Remove any success/error messages
  const msgEl = section.querySelector(".edit-message");
  if (msgEl) {
    msgEl.remove();
  }
}

/**
 * Go back to previous step
 */
function goBack() {
  window.location.href = "customer-onboarding-security.html";
}

/**
 * Validate terms acceptance
 */
function validateTerms() {
  const termsCheckbox = document.getElementById("terms-checkbox");
  const termsError = document.getElementById("terms-error");

  if (!termsCheckbox.checked) {
    termsError.textContent = "You must accept the terms and conditions";
    termsCheckbox.parentElement.classList.add("is-invalid");
    return false;
  }

  return true;
}

/**
 * Clear terms error
 */
function clearTermsError() {
  const termsError = document.getElementById("terms-error");
  const termsCheckbox = document.getElementById("terms-checkbox");

  if (termsError) {
    termsError.textContent = "";
  }

  if (termsCheckbox) {
    termsCheckbox.parentElement.classList.remove("is-invalid");
  }
}

/**
 * Submit final application
 */
async function submitApplication() {
  // Validate terms
  if (!validateTerms()) {
    return;
  }

  const submitBtn = document.getElementById("submit-btn");
  if (!submitBtn) {
    console.error("Submit button not found!");
    return;
  }

  const btnText = submitBtn.querySelector(".btn-text");
  const spinner = submitBtn.querySelector(".spinner-border");

  try {
    // Disable button and show spinner
    submitBtn.disabled = true;
    btnText.classList.add("d-none");
    spinner.classList.remove("d-none");

    // Get data from sessionStorage and send it to the API
    const step1Data = sessionStorage.getItem("onboarding_step1");
    const step2Data = sessionStorage.getItem("onboarding_step2");

    if (!step1Data) {
      throw new Error("Session data missing. Please start from the beginning.");
    }

    // Combine step 1 and step 2 data
    const step1Parsed = JSON.parse(step1Data);
    const step2Parsed = step2Data ? JSON.parse(step2Data) : {};

    console.log("🔍 Step2 Data:", step2Parsed);
    console.log("🔍 id_type from step2:", step2Parsed.id_type);
    console.log("🔍 id_number from step2:", step2Parsed.id_number);

    // Create FormData to handle file uploads
    const formData = new FormData();

    // Add all step 1 fields
    for (const [key, value] of Object.entries(step1Parsed)) {
      if (value !== null && value !== undefined) {
        formData.append(
          key,
          typeof value === "object" ? JSON.stringify(value) : value
        );
      }
    }

    // Add step 2 fields (excluding file data)
    for (const [key, value] of Object.entries(step2Parsed)) {
      if (
        key.endsWith("_data") ||
        key === "id_front_name" ||
        key === "id_back_name" ||
        key === "id_front_type" ||
        key === "id_back_type"
      ) {
        continue; // Skip file data fields for now
      }
      if (value !== null && value !== undefined) {
        console.log(`✅ Adding step2 field: ${key} = ${value}`);
        formData.append(
          key,
          typeof value === "object" ? JSON.stringify(value) : value
        );
      }
    }

    // Convert base64 files back to Blob and add to FormData
    if (step2Parsed.id_front_data) {
      const frontBlob = await fetch(step2Parsed.id_front_data).then((r) =>
        r.blob()
      );
      formData.append(
        "id_front_image",
        frontBlob,
        step2Parsed.id_front_name || "id_front.jpg"
      );
    }

    if (step2Parsed.id_back_data) {
      const backBlob = await fetch(step2Parsed.id_back_data).then((r) =>
        r.blob()
      );
      formData.append(
        "id_back_image",
        backBlob,
        step2Parsed.id_back_name || "id_back.jpg"
      );
    }

    console.log("Sending form data to API with files");

    const response = await fetch(`${API_BASE_URL}/customer/create-final.php`, {
      method: "POST",
      credentials: "include",
      body: formData,
    });

    // Check if response is ok and content-type is JSON
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      // Response is not JSON, likely a PHP error
      const text = await response.text();
      console.error("Non-JSON response from server:", text);
      throw new Error(
        "Server returned an error. Please check if XAMPP is running and the database is accessible."
      );
    }

    const result = await response.json();

    if (result.success) {
      // Show success modal with account number
      showSuccessModal(result.account_number);
    } else {
      // Handle errors with inline message
      let errorMsg = "";
      if (result.errors) {
        errorMsg = "Please fix the following errors: ";
        const errorList = [];
        for (const field in result.errors) {
          errorList.push(result.errors[field]);
        }
        errorMsg += errorList.join(", ");
      } else {
        errorMsg = result.message || "An error occurred during submission";
      }

      showGlobalError(errorMsg);

      // Re-enable button
      submitBtn.disabled = false;
      if (btnText) btnText.classList.remove("d-none");
      if (spinner) spinner.classList.add("d-none");
    }
  } catch (error) {
    console.error("Error submitting application:", error);
    showGlobalError("An error occurred while submitting your application");

    // Re-enable button
    if (submitBtn) {
      submitBtn.disabled = false;
      if (btnText) btnText.classList.remove("d-none");
      if (spinner) spinner.classList.add("d-none");
    }
  }
}

/**
 * Show success modal
 */
function showSuccessModal(accountNumber) {
  const accountNumberEl = document.getElementById("account-number");
  if (accountNumberEl) {
    accountNumberEl.textContent = accountNumber;
  }

  const successModal = new bootstrap.Modal(
    document.getElementById("successModal")
  );
  successModal.show();
}

/**
 * Go to employee dashboard (after successful account creation)
 */
function goToLogin() {
  // Clear session storage
  sessionStorage.clear();

  // Redirect to employee dashboard
  window.location.href = "employee-dashboard.html";
}

/**
 * Show global error message at top of form
 */
function showGlobalError(message) {
  // Remove existing global error
  const existingError = document.querySelector(".global-error-message");
  if (existingError) {
    existingError.remove();
  }

  // Create error element
  const errorEl = document.createElement("div");
  errorEl.className = "global-error-message alert alert-danger";
  errorEl.style.marginBottom = "20px";
  errorEl.textContent = message;

  // Insert at top of review card
  const reviewCard = document.querySelector(".review-card");
  if (reviewCard) {
    reviewCard.insertBefore(errorEl, reviewCard.firstChild);

    // Auto-remove after 5 seconds
    setTimeout(() => {
      errorEl.remove();
    }, 5000);
  }
}
