<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<div class="container-fluid ">
  <?php if (!empty($data['account_number_error'])): ?>
      <div class="alert alert-danger alert-message"><?= $data['account_number_error']; ?></div>
  <?php endif; ?>

  <?php if (!empty($data['account_type_error'])): ?>
      <div class="alert alert-danger alert-message"><?= $data['account_type_error']; ?></div>
  <?php endif; ?>

  <?php if (isset($_SESSION['flash_success'])): ?>
      <div class="alert alert-success alert-message"><?= $_SESSION['flash_success']; ?></div>
      <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
    <div class="row">
        <!----------------------- LEFT SIDEBAR ------------------------------------------------------------------------------------->
        <div class="col-md-5 col-lg-4 pt-5 px-5" style="background-color: #D9D9D94D;">
            <h4 class="fw-normal">Your <br></h4>
            <h4 class="fw-bold"> Accounts</h4>

            <!------------------- SEARCH BAR AND FILTER ---------------------------------------------------------------------------->
            <div class="d-flex align-items-center justify-content-between bg-light shadow-sm px-3 py-2 mb-4" style="max-width: 400px;">
                <div class="d-flex align-items-center flex-grow-1">
                    <input type="text" class="form-control border-0 bg-transparent" placeholder="Search" style="box-shadow: none;" id="accountSearchInput">
                    <i class="bi bi-search text-muted "></i>
                </div>
                <div class="vr mx-2 my-2"></div>

                <!--------------- FILTER BUTTON ------------------------------------------------------------------------------------>
                <div class="dropdown">
                    <a class="d-flex align-items-center text-decoration-none text-muted" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-sliders me-1 fs-5" style="transform:rotate(90deg);"></i>
                    <span class="ms-1 small">Filter</span>
                    </a>

                    <!----------- FILTER DROPDOWN  ---------------------------------------------------------------------------------->
                    <div class="dropdown-menu dropdown-menu-end p-3 shadow" style="width: 220px;">
                        <h6 class="fw-bold mb-2">Filter Accounts</h6>
                        <small class="fw-bold text-muted">By Account Type</small>
                        <div class="form-check">
                            <input class="form-check-input filter-checkbox" type="checkbox" id="filterSavings" data-account-type="savings">
                            <label class="form-check-label" for="filterSavings">Savings Accounts</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input filter-checkbox" type="checkbox" id="filterChecking" data-account-type="checking">
                            <label class="form-check-label" for="filterChecking">Checking Accounts</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input filter-checkbox" type="checkbox" id="filterCredit" data-account-type="credit card">
                            <label class="form-check-label" for="filterCredit">Credit Cards</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input filter-checkbox" type="checkbox" id="filterLoan" data-account-type="loan">
                            <label class="form-check-label" for="filterLoan">Loan Accounts</label>
                        </div>
                        <small class="fw-bold text-muted">By Account Status</small>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active">
                            <label class="form-check-label" for="active">Active</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="closed">
                            <label class="form-check-label" for="closed">Closed</label>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-success btn-sm rounded-pill px-3" id="applyFiltersBtn">Apply Filters</button>
                            <button class="btn btn-link btn-sm text-muted" id="resetFiltersBtn">Reset</button>
                        </div>
                    </div>
                </div>
            </div>


            <?php
            // Re-group accounts here to ensure it's available for the entire view,
            // especially if the controller only passes a flat list.
            $groupedAccounts = [];
            if (isset($data['accounts']) && is_array($data['accounts'])) {
                foreach ($data['accounts'] as $account) {
                    $type = strtolower($account->account_type);
                    if (str_contains($type, 'savings')) {
                        $groupedAccounts['Savings Accounts'][] = $account;
                    } elseif (str_contains($type, 'checking')) {
                        $groupedAccounts['Checking Accounts'][] = $account;
                    } elseif (str_contains($type, 'credit card')) {
                        $groupedAccounts['Credit Cards'][] = $account;
                    } elseif (str_contains($type, 'loan')) {
                        $groupedAccounts['Loan Accounts'][] = $account;
                    } 
                     else {
                        $groupedAccounts['Other Accounts'][] = $account; // Fallback for other types
                    }
                }
            }
            $firstAccount = $data['accounts'][0] ?? null; // Get the first account for initial display
            ?>

                          <?php
              $firstAccountCardRendered = false; // Flag to mark the first card
              foreach ($groupedAccounts as $groupName => $accountsInGroup): ?>
                  <div class="mb-4 account-group-container" data-group-name="<?= htmlspecialchars($groupName); ?>">
                      <h6 class="fw-normal ms-2 account-group-title"><?= htmlspecialchars($groupName); ?></h6>
                      <?php foreach ($accountsInGroup as $account):
                          $isSavingsAccount = str_contains(strtolower($account->account_type), 'savings');
                          $cardClasses = ['card', 'border-0', 'shadow-sm', 'mb-2', 'account-card'];
                          $cardStyle = '';

                          // Mark the first card as active
                          if (!$firstAccountCardRendered) {
                              $cardClasses[] = 'active';
                              $firstAccountCardRendered = true;
                          }
                          ?>
                          <div class="<?= implode(' ', $cardClasses); ?>"
                              data-account-id="<?= htmlspecialchars($account->account_id); ?>"
                              data-account-number="<?= htmlspecialchars($account->account_number); ?>"
                              data-account-name="<?= htmlspecialchars($account->account_name); ?>"
                              data-account-type="<?= htmlspecialchars($account->account_type); ?>"
                              data-branch="<?= htmlspecialchars($account->branch ?? 'Main Branch'); ?>"
                              data-available-balance="<?= htmlspecialchars(number_format($account->ending_balance ?? 0, 2, '.', '')); ?>"
                              data-available-credit="<?= htmlspecialchars(number_format($account->available_credit ?? 0, 2, '.', '')); ?>"
                              data-credit-limit="<?= htmlspecialchars(number_format($account->credit_limit ?? 0, 2, '.', '')); ?>"
                              data-transactions='<?= json_encode($account->transactions ?? []); ?>'
                              data-group-name="<?= htmlspecialchars($groupName); ?>"
                              style="cursor: pointer;">
                              <div class="card-body rounded position-relative">
                                  <div class="d-flex justify-content-between align-items-start">
                                      <div class="d-flex align-items-start">
                                          <?php if (str_contains(strtolower($account->account_type), 'savings')): ?>
                                              <i class="bi bi-credit-card fs-2 text-dark"></i>
                                          <?php elseif (str_contains(strtolower($account->account_type), 'checking')): ?>
                                              <i class="bi bi-credit-card-2-front fs-2 text-dark"></i>
                                          <?php elseif (str_contains(strtolower($account->account_type), 'credit card')): ?>
                                              <i class="bi bi-credit-card-fill fs-2 text-dark"></i>
                                          <?php elseif (str_contains(strtolower($account->account_type), 'loan')): ?>
                                              <i class="bi bi-currency-dollar fs-2 text-dark"></i>
                                          <?php else: ?>
                                              <i class="bi bi-wallet2 fs-2 text-dark"></i>
                                          <?php endif; ?>
                                          <div>
                                              <h6 class="fw-bold mb-1 ms-3"><?= htmlspecialchars($account->account_name); ?></h6>
                                              <small class="text-muted d-block ms-3"><?= htmlspecialchars($account->account_number); ?></small>
                                          </div>
                                      </div>
                                  </div>
                                  <div class="mt-3">
                                      <?php if (str_contains(strtolower($account->account_type), 'credit card')): ?>
                                          <?php
                                              $creditUsed = ($account->credit_limit ?? 0) - ($account->available_credit ?? 0);
                                              $creditUsagePercentage = ($account->credit_limit > 0) ? ($creditUsed / $account->credit_limit) * 100 : 0;
                                              $creditUsagePercentage = max(0, min(100, $creditUsagePercentage));
                                          ?>
                                          <div class="progress mb-2" style="height: 8px; background-color: #e9ecef; border-radius: 10px;">
                                              <div class="progress-bar"
                                                  role="progressbar"
                                                  style="width: <?= $creditUsagePercentage; ?>%; background-color: #004d40; border-radius: 10px;">
                                              </div>
                                          </div>
                                          <div class="d-flex justify-content-between">
                                              <small class="text-muted">Available Credit</small>
                                              <small class="text-muted">PHP <?= number_format($account->available_credit ?? 0, 2); ?> / <?= number_format($account->credit_limit ?? 0, 2); ?> Limit</small>
                                          </div>
                                      <?php else: ?>
                                          <div class="d-flex justify-content-between">
                                              <small class="text-muted">Available Balance</small>
                                              <small class="text-muted">PHP <?= number_format($account->ending_balance ?? 0, 2); ?></small>
                                          </div>
                                          <div class="d-flex justify-content-between">
                                              <small class="text-muted">Current Balance</small>
                                              <small class="text-muted">PHP <?= number_format($account->ending_balance ?? 0, 2); ?></small>
                                          </div>
                                      <?php endif; ?>
                                  </div>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  </div>
              <?php endforeach; ?>

            <!------------------- MANAGE ACCOUNTS -------------------------------------------------------------------------------------->
            <button class="btn w-100 my-2" data-bs-toggle="modal" data-bs-target="#manageAccountsModal" style="background-color: #F1B24A;">
                Manage Accounts
            </button>

            <!-- MANAGE ACCOUNTS POP UP ------------------------------------------------------------------------------------------------->
            <div class="modal fade" id="manageAccountsModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 410px;">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <!-- HEADER ------------------------------------------------------------------------------------------>
                        <div class="modal-header rounded-top-4" style="background-color:#003631; color:white;">
                            <h5 class="modal-title fw-semibold">Manage Accounts</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <!-- BODY ------------------------------------------------------------------------------------------>
                        <div class="modal-body rounded-bottom-3 p-4" style="background-color: #D9D9D9;">
                            <!-- ADD OR REMOVE ------------------------------------------------------------------------------------------>
                            <button class="btn w-100 mb-3 py-3 bg-white rounded-4 text-start border-0 shadow-sm"
                                    data-bs-toggle="modal" data-bs-target="#addRemoveAccountModal" data-bs-dismiss="modal">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-plus-square fs-3 ms-2 me-3 text-dark"></i>
                                    <div>
                                    <div class="fw-semibold text-dark">Add or Remove Account</div>
                                    <small class="text-muted">Add or remove your accounts</small>
                                    </div>
                                </div>
                                </button>

                                <!-- SHOW OR HIDE ------------------------------------------------------------------------------------------>
                                <button class="btn w-100 py-3 bg-white rounded-4 text-start border-0 shadow-sm"
                                        data-bs-toggle="modal" data-bs-target="#showHideAccountModal" data-bs-dismiss="modal">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-eye-slash fs-3 ms-2 me-3 text-dark"></i>
                                    <div>
                                        <div class="fw-semibold text-dark">Show or Hide Account</div>
                                        <small class="text-muted">Select which accounts to display</small>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ADD / REMOVE ACCOUNT POP UP ----------------------------------------------------------------------->
            <div class="modal fade" id="addRemoveAccountModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 410px;">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <!-- HEADER ------------------------------------------------------------------------------------------>
                        <div class="modal-header rounded-top-4 d-flex align-items-center" style="background-color:#003631; color:white;">
                            <button class="btn text-white me-2 p-0" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#manageAccountsModal" style="font-size:1.3rem; line-height:1;">
                                <i class="bi bi-chevron-left fs-6 me-1"></i>
                            </button>
                            <h5 class="modal-title fw-semibold mb-0">Add or Remove Account</h5>
                        </div>

                        <!-- BODY ------------------------------------------------------------------------------------------>
                        <div class="modal-body rounded-bottom-3" style="background-color: #D9D9D9 ;">
                            <!-- ADD ACCOUNT BUTTON ------------------------------------------------------------------------------------------>
                            <button class="btn w-100 mb-3 d-flex justify-content-between align-items-center bg-white rounded-4 border-0 shadow-sm py-3 px-3"
                                    data-bs-toggle="modal" data-bs-target="#addAccountModal" data-bs-dismiss="modal">
                                <div class="d-flex align-items-center ms-2">
                                    <i class="bi bi-plus-square fs-3 me-3 ms-2 text-dark"></i>
                                    <span class="fw-semibold text-dark ms-2">Add Account</span>
                                </div>
                                <i class="bi bi-chevron-right fs-5 text-dark"></i>
                            </button>

                            <?php foreach ($groupedAccounts as $groupName => $accountsInGroup): ?>
                                <?php if (!empty($accountsInGroup)): ?>
                                    <div class="bg-white rounded-4 px-3 py-2 mb-3 shadow-sm ">
                                        <p class="fw-semibold text-muted mb-2 ms-1"><?= htmlspecialchars($groupName); ?></p>
                                        <?php foreach ($accountsInGroup as $account): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="d-flex align-items-start ms-3">
                                                    <?php // Icon based on account type ?>
                                                    <?php if (str_contains(strtolower($account->account_type), 'savings')): ?>
                                                        <i class="bi bi-wallet2 fs-3 mt-1 me-4 text-dark"></i>
                                                    <?php elseif (str_contains(strtolower($account->account_type), 'checking')): ?>
                                                        <i class="bi bi-credit-card-2-front fs-3 mt-1 me-4 text-dark"></i>
                                                    <?php elseif (str_contains(strtolower($account->account_type), 'credit card')): ?>
                                                        <i class="bi bi-credit-card-fill fs-3 mt-1 me-4 text-dark"></i>
                                                    <?php elseif (str_contains(strtolower($account->account_type), 'loan')): ?>
                                                        <i class="bi bi-currency-dollar fs-3 mt-1 me-4 text-dark"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-wallet2 fs-3 mt-1 me-4 text-dark"></i>
                                                    <?php endif; ?>
                                                    <div class="ms-2">
                                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($account->account_name); ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($account->account_number); ?></small>
                                                    </div>
                                                </div>
                                                <button class="btn btn-link text-danger p-0"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteAccountModal"
                                                        data-bs-dismiss="modal"
                                                        data-account-id="<?= htmlspecialchars($account->account_id); ?>"
                                                        data-account-name="<?= htmlspecialchars($account->account_name); ?>"
                                                        data-account-number="<?= htmlspecialchars($account->account_number); ?>">
                                                    <i class="bi bi-dash-circle fs-4"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>


            <!-- SHOW / HIDE ACCOUNT POP UP -------------------------------------------------------------------------------->
            <div class="modal fade" id="showHideAccountModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 410px;">
                    <div class="modal-content rounded-4 border-0 shadow">

                        <!-- HEADER ------------------------------------------------------------------------------------------>
                        <div class="modal-header d-flex align-items-center" style="background-color: #003631; color: white;">
                            <button class="btn text-white me-2 p-0" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#manageAccountsModal" style="font-size: 1.2rem;">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <h5 class="modal-title mb-0 fw-semibold">Show or Hide Account</h5>
                        </div>

                        <!-- BODY ------------------------------------------------------------------------------------------>
                        <div class="modal-body rounded-bottom-3" style="background-color: #D9D9D9;">

                            <?php foreach ($groupedAccounts as $groupName => $accountsInGroup): ?>
                                <?php if (!empty($accountsInGroup)): ?>
                                    <div class="bg-white p-3 mb-3 rounded-4 shadow-sm">
                                        <div class="fw-semibold small mb-2"><?= htmlspecialchars($groupName); ?></div>
                                        <?php foreach ($accountsInGroup as $account): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="d-flex align-items-center ms-2">
                                                    <?php if (str_contains(strtolower($account->account_type), 'savings')): ?>
                                                        <i class="bi bi-wallet2 me-3 fs-4"></i>
                                                    <?php elseif (str_contains(strtolower($account->account_type), 'checking')): ?>
                                                        <i class="bi bi-credit-card-2-front me-3 fs-4"></i>
                                                    <?php elseif (str_contains(strtolower($account->account_type), 'credit card')): ?>
                                                        <i class="bi bi-credit-card-fill me-3 fs-4"></i>
                                                    <?php elseif (str_contains(strtolower($account->account_type), 'loan')): ?>
                                                        <i class="bi bi-currency-dollar me-3 fs-4"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-wallet2 me-3 fs-4"></i>
                                                    <?php endif; ?>
                                                    <div class="ms-2">
                                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($account->account_name); ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($account->account_number); ?></small>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" id="showHideToggle_<?= htmlspecialchars($account->account_id); ?>"
                                                           name="show_hide_account_<?= htmlspecialchars($account->account_id); ?>"
                                                           <?= $account->is_hidden ?? false ? '' : 'checked'; ?>>
                                                    <label class="form-check-label visually-hidden" for="showHideToggle_<?= htmlspecialchars($account->account_id); ?>">Toggle visibility for <?= htmlspecialchars($account->account_name); ?></label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADD ACCOUNT POP UP ------------------------------------------------------------------------------------------>
            <div class="modal fade" id="addAccountModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <!-- HEADER ------------------------------------------------------------------------------------------>
                        <div class="modal-header rounded-top-4 d-flex align-items-center" style="background-color:#003631; color:white;">
                            <button class="btn text-white me-2 p-0" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addRemoveAccountModal" style="font-size:1.3rem; line-height:1;">
                                <i class="bi bi-arrow-left"></i>
                            </button>
                            <h5 class="modal-title fw-semibold mb-0">Add New Account</h5>
                        </div>

                        <!-- BODY ------------------------------------------------------------------------------------------>
                        <div class="modal-body rounded-bottom-3" style="background-color:#D9D9D9;">
                            <form id="addAccountForm" action=<?= URLROOT . "/customer/addAccount"?> method="POST">
                                <div class="mb-3 bg-light rounded-4 p-3 shadow-sm">
                                    <label for="accountType" class="form-label fw-semibold text-dark mb-1 ms-2">Account Type</label>
                                    <select class="form-select border-1 border-secondary rounded-3 bg-body-secondary" id="accountType" name="account_type" required>
                                        <option value="">Select Account Type</option>
                                        <option value="Savings">Savings Account</option>
                                        <option value="Checking">Checking Account</option>
                                        <option value="Loan">Loan Account</option>
                                        <option value="Fixed Deposit">Fixed Deposit</option>
                                    </select>
                                </div>

                                <!-- ACCOUNT NUMBER ------------------------------------------------------------------------------------------>
                                <div class="mb-3 bg-light rounded-4 p-3 shadow-sm">
                                    <label for="accountNumber" class="form-label fw-semibold text-dark mb-2 ms-2">Account Number</label>
                                    <input type="text" class="form-control border-1 border-secondary rounded-3 mb-4 py-2" id="accountNumber" name="account_number" placeholder="Enter account number" required>

                                    <!-- PREFERRED NAME ------------------------------------------------------------------------------------------>
                                    <label for="preferredName" class="form-label fw-semibold text-dark mb-2 ms-2">Preferred Name
                                        <span class="text-muted fw-normal fst-italic">(Optional)</span>
                                    </label>
                                    <input type="text" class="form-control border-1 border-secondary rounded-3 py-2" id="preferredName" name="account_name" placeholder="Enter name">
                                </div>

                                <!-- TERMS AND CONDITIONS ------------------------------------------------------------------------------------------>
                                <div class="text-center mb-3">
                                    <input class="form-check-input me-2" type="checkbox" id="termsCheck" required>
                                    <label class="form-check-label small" for="termsCheck">
                                        I accept the <a href="#" class="text-decoration-none text-success">Terms and Agreements</a>
                                    </label>
                                </div>

                                <!-- ADD BUTTON ------------------------------------------------------------------------------------------>
                                <div class="text-center">
                                    <button type="submit" class="btn w-75 text-white fw-bold fs-5 py-2 rounded-3" style="background-color:#003631;">
                                        Add
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <!-- DELETE ACCOUNT POP UP ---------------------------------------------------------------------------------------------->
            <div class="modal fade" id="deleteAccountModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
                    <div class="modal-content rounded-4 border-0">
                        <div class="modal-header" style="background-color: #003631; color: white;">
                            <h5 class="modal-title">Delete Account?</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body rounded-bottom-3 text-center" style="background-color:#D9D9D9;">
                            <div class="card bg-white rounded-4 py-2 px-2 mb-3">
                                <!-- Message will be updated by JS from data-attributes of the trigger button -->
                                <p class="mt-3" id="deleteAccountMessage">Are you sure you want to delete this account?</p>
                            </div>
                            <div class="d-flex justify-content-center gap-3 px-4">
                                <button class="btn btn-secondary w-50 rounded-4 border-0 text-dark px-4 shadow-lg" style="background-color:#D9D9D9;" data-bs-dismiss="modal">Cancel</button>
                                <form id="confirmDeleteForm" action=<?= URLROOT. "/customer/removeAccount" ?> method="POST" style="display:inline;">
                                    <input type="hidden" name="account_id" id="deleteAccountIdInput">
                                    <button type="submit" class="btn btn-danger w-100 text-light px-4 shadow-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!----------------------- MAIN CONTENT ------------------------------------------------------------------------------------->
        <div class="col-md-7 col-lg-8 pt-5 shadow-sm" id="main-account-content">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0 px-4"> <?= htmlspecialchars($data['user_name'] ?? ($data['first_name'] . ' ' . $data['last_name']) ?? 'Customer'); ?></h4>
            </div>

            <hr>

            <!------------------- ACCOUNT DETAILS ---------------------------------------------------------------------------------->
            <div class="card border-0 shadow-sm mb-4 px-4" id="account-details-card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Account Details</h6>
                    <div class="row mb-4 d-flex">
                        <div class="col-md-3">
                            <p class="mb-0 text-muted">Account Number</p>
                            <p id="detail-account-number"><?= htmlspecialchars($firstAccount->account_number ?? 'N/A');?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-0 text-muted">Account Name</p>
                            <p id="detail-account-name"> <?= htmlspecialchars($firstAccount->account_name ?? ($data['first_name'] . ' ' . $data['last_name']) ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-0 text-muted">Account Type</p>
                            <p id="detail-account-type"><?= htmlspecialchars($firstAccount->account_type ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-0 text-muted">Branch</p>
                            <p id="detail-branch"><?= htmlspecialchars($firstAccount->branch ?? 'Main Branch'); ?></p>
                        </div>
                    </div>
                    <div class="row" id="balance-row" style="display: <?= (!empty($firstAccount) && str_contains(strtolower($firstAccount->account_type), 'credit card')) ? 'none' : 'flex'; ?>;">
                        <div class="col-md-9 text-muted"><p>Available Balance:</p></div>
                        <div class="col-md-3"><p id="detail-available-balance">PHP <?= number_format($firstAccount->ending_balance ?? 0, 2); ?></p></div>
                    </div>
                    <!-- Credit card specific details (initially hidden) -->
                    <div id="credit-card-details" style="display: <?= (!empty($firstAccount) && str_contains(strtolower($firstAccount->account_type ?? ''), 'credit card')) ? 'block' : 'none'; ?>;">
                        <hr>
                        <h6 class="fw-bold mb-3">Credit Card Details</h6>
                        <div class="row">
                            <div class="col-md-9 text-muted"><p>Available Credit:</p></div>
                            <div class="col-md-3"><p id="detail-available-credit">PHP <?= number_format($firstAccount->available_credit ?? 0, 2); ?></p></div>
                        </div>
                        <div class="row">
                            <div class="col-md-9 text-muted"><p>Credit Limit:</p></div>
                            <div class="col-md-3"><p id="detail-credit-limit">PHP <?= number_format($firstAccount->credit_limit ?? 0, 2); ?></p></div>
                        </div>
                    </div>
                </div>
            </div>

            <!------------------- TRANSACTION HISTORY ------------------------------------------------------------------------------>
            <div class="card border-0 shadow-sm px-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-2">Transaction History</h6>
                        <a href=<?= URLROOT . "/customer/transaction_history"?> class="text-success small me-4">View All</a>
                    </div>

                    <!----------- TRANSACTIONS ------------------------------------------------------------------------------------->
                    <div class="list-group" id="transaction-history-list">
                        <?php if (!empty($firstAccount->transactions)): ?>
                            <?php foreach ($firstAccount->transactions as $transaction): ?>
                                <?php
                                // Normalize the transaction type name for reliable checking
                                $typeName = strtolower($transaction->transaction_type_name);
                                
                                // Define CREDIT transactions (Money coming IN)
                                $isCredit = ($typeName == 'deposit' || $typeName == 'transfer in' || $typeName == 'interest payment' || $typeName == 'loan disbursement');
                                
                                // Define DEBIT transactions (Money going OUT)
                                $isDebit = ($typeName == 'withdrawal' || $typeName == 'transfer out' || $typeName == 'service charge' || $typeName == 'loan payment');
                                
                                if ($isCredit) {
                                    $iconClass = 'bi-arrow-down-left text-success';
                                    $amountSign = ''; 
                                    $amountColor = 'text-success';
                                    $descriptionColor = 'text-success'; // Descriptive text for Credit transactions
                                } elseif ($isDebit) {
                                    $iconClass = 'bi-arrow-up-right text-danger';
                                    $amountSign = '-';
                                    $amountColor = 'text-danger';
                                    $descriptionColor = 'text-dark'; // Descriptive text for Debit transactions
                                } else {
                                    $iconClass = 'bi-exclamation-triangle-fill text-secondary';
                                    $amountSign = '';
                                    $amountColor = 'text-secondary';
                                    $descriptionColor = 'text-secondary';
                                }
                                ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center border rounded mb-4" style="background-color: #D9D9D94D;">
                                    <div>
                                        <div class="row">
                                            <div class="col-3 mt-2">
                                                <i class="bi <?= $iconClass; ?> fs-2"></i>
                                            </div>
                                            <div class="col-8">
                                                <strong class="<?= $descriptionColor; ?>">
                                                    <?= htmlspecialchars($transaction->transaction_type_name); ?>
                                                </strong><br>
                                                <small class="text-muted">Transaction ID</small><br>
                                                <small><?= htmlspecialchars($transaction->transaction_ref); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="<?= $amountColor; ?> fw-semibold"><?= $amountSign; ?>PHP <?= number_format($transaction->amount, 2); ?></span><br>
                                        <small class="text-muted"><?= date('d F Y', strtotime($transaction->created_at)); ?><br><?= date('h:i A', strtotime($transaction->created_at)); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No transactions to display for this account.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // --- Alert Message Handling ---
    const alerts = document.querySelectorAll('.alert-message');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    }

    // --- Delete Account Modal Handling ---
    const deleteAccountModal = document.getElementById('deleteAccountModal');
    if (deleteAccountModal) {
        deleteAccountModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const accountId = button.dataset.accountId;
            const accountName = button.dataset.accountName;
            const accountNumber = button.dataset.accountNumber;

            const modalMessage = deleteAccountModal.querySelector('#deleteAccountMessage');
            modalMessage.innerHTML = `Are you sure you want to delete <br> <strong>${accountName} (${accountNumber})</strong>?`;

            const deleteAccountIdInput = deleteAccountModal.querySelector('#deleteAccountIdInput');
            deleteAccountIdInput.value = accountId;
        });
    }

    // --- DOM Element References ---
    const accountCards = document.querySelectorAll('.account-card');
    const detailAccountNumber = document.getElementById('detail-account-number');
    const detailAccountName = document.getElementById('detail-account-name');
    const detailAccountType = document.getElementById('detail-account-type');
    const detailBranch = document.getElementById('detail-branch');
    const detailAvailableBalance = document.getElementById('detail-available-balance');
    const balanceRow = document.getElementById('balance-row');
    const creditCardDetails = document.getElementById('credit-card-details');
    const detailAvailableCredit = document.getElementById('detail-available-credit');
    const detailCreditLimit = document.getElementById('detail-credit-limit');
    const transactionHistoryList = document.getElementById('transaction-history-list');
    const accountSearchInput = document.getElementById('accountSearchInput');
    const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const accountGroupContainers = document.querySelectorAll('.account-group-container');
    const showHideAccountModalElement = document.getElementById('showHideAccountModal');


    // --- Helper Function: Currency Formatting ---
    function formatCurrency(amount) {
        return parseFloat(amount).toLocaleString('en-PH', {
            style: 'currency',
            currency: 'PHP'
        });
    }

    // --- Function to Update Main Content Area ---
    function updateMainContent(card) {
        // Update Account Details
        detailAccountNumber.textContent = card.dataset.accountNumber;
        detailAccountName.textContent = card.dataset.accountName;
        detailAccountType.textContent = card.dataset.accountType;
        detailBranch.textContent = card.dataset.branch;

        const accountType = card.dataset.accountType.toLowerCase();

        if (accountType.includes('credit card')) {
            balanceRow.style.display = 'none';
            creditCardDetails.style.display = 'block';
            detailAvailableCredit.textContent = formatCurrency(card.dataset.availableCredit);
            detailCreditLimit.textContent = formatCurrency(card.dataset.creditLimit);
        } else {
            balanceRow.style.display = 'flex';
            creditCardDetails.style.display = 'none';
            detailAvailableBalance.textContent = formatCurrency(card.dataset.availableBalance);
        }

        // Update Transaction History
        const transactions = JSON.parse(card.dataset.transactions || '[]');
        transactionHistoryList.innerHTML = ''; // Clear existing transactions

        if (transactions.length > 0) {
            transactions.forEach(transaction => {

                const typeName = transaction.transaction_type_name.toLowerCase();

                // Define Credit (Money In) and Debit (Money Out) transactions
                const isCredit = (typeName === 'deposit' || typeName === 'transfer in' || typeName === 'interest payment' || typeName === 'loan disbursement');
                const isDebit = (typeName === 'withdrawal' || typeName === 'transfer out' || typeName === 'service charge' || typeName === 'loan payment');

                let iconClass;
                let amountSign;
                let amountColor;
                let descriptionColor;

                if (isCredit) {
                    // Funds coming IN: Green arrow, no sign
                    iconClass = 'bi-arrow-down-left text-success';
                    amountSign = '';
                    amountColor = 'text-success';
                    descriptionColor = 'text-success';
                } else if (isDebit) {
                    // Funds going OUT: Red arrow, minus sign
                    iconClass = 'bi-arrow-up-right text-danger';
                    amountSign = '-';
                    amountColor = 'text-danger';
                    descriptionColor = 'text-dark';
                } else {
                    // Fallback for unknown types
                    iconClass = 'bi-exclamation-triangle-fill text-secondary';
                    amountSign = '';
                    amountColor = 'text-secondary';
                    descriptionColor = 'text-secondary';
                }

                const transactionDate = new Date(transaction.created_at);
                const formattedDate = transactionDate.toLocaleDateString('en-PH', { day: '2-digit', month: 'long', year: 'numeric' });
                const formattedTime = transactionDate.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true });

                const transactionItem = `
                    <div class="list-group-item d-flex justify-content-between align-items-center border rounded mb-4" style="background-color: #D9D9D94D;">
                        <div>
                            <div class="row">
                                <div class="col-3 mt-2">
                                    <i class="bi ${iconClass} fs-2"></i>
                                </div>
                                <div class="col-9">
                                    <strong class="${descriptionColor}">${transaction.transaction_type_name}</strong><br>
                                    <small class="text-muted">Transaction ID</small><br>
                                    <small>${transaction.transaction_ref == null ? 'N/A' : transaction.transaction_ref}</small>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="${amountColor} fw-semibold">${amountSign}${formatCurrency(transaction.amount)}</span><br>
                            <small class="text-muted">${formattedDate}<br>${formattedTime}</small>
                        </div>
                    </div>
                `;
                transactionHistoryList.innerHTML += transactionItem;
            });
        } else {
            transactionHistoryList.innerHTML = '<p class="text-muted text-center">No transactions to display for this account.</p>';
        }
    }


    // --- Function to Filter and Search Accounts ---
    function filterAndSearchAccounts() {
        const searchTerm = accountSearchInput.value.toLowerCase();
        const selectedAccountTypes = Array.from(filterCheckboxes)
                                        .filter(cb => cb.checked)
                                        .map(cb => cb.dataset.accountType.toLowerCase());

        // Get current show/hide status from the toggles in the modal
        const showHideStates = {};
        document.querySelectorAll('#showHideAccountModal .form-check-input').forEach(toggle => {
            const accountId = toggle.id.replace('showHideToggle_', '');
            showHideStates[accountId] = toggle.checked; // true for show, false for hide
        });

        let firstVisibleCard = null;

        accountGroupContainers.forEach(groupContainer => {
            let groupHasVisibleCards = false;
            const cardsInGroup = groupContainer.querySelectorAll('.account-card');
            const groupTitle = groupContainer.querySelector('.account-group-title');

            cardsInGroup.forEach(card => {
                const accountId = card.dataset.accountId;
                const accountName = card.dataset.accountName.toLowerCase();
                const accountNumber = card.dataset.accountNumber.toLowerCase();
                const accountType = card.dataset.accountType.toLowerCase();

                const matchesSearch = accountName.includes(searchTerm) || accountNumber.includes(searchTerm);
                const matchesTypeFilter = selectedAccountTypes.length === 0 || selectedAccountTypes.includes(accountType);
                // Default to show if no toggle exists for this account, or if the toggle is checked
                const isExplicitlyShown = showHideStates[accountId] === undefined || showHideStates[accountId];

                if (matchesSearch && matchesTypeFilter && isExplicitlyShown) {
                    card.style.display = ''; // Show card
                    groupHasVisibleCards = true;
                    if (!firstVisibleCard) {
                        firstVisibleCard = card;
                    }
                } else {
                    card.style.display = 'none'; // Hide card
                }
            });

            // Show/hide group title and container based on whether it has visible cards
            if (groupHasVisibleCards) {
                groupContainer.style.display = '';
                if (groupTitle) groupTitle.style.display = '';
            } else {
                groupContainer.style.display = 'none';
                if (groupTitle) groupTitle.style.display = 'none';
            }
        });

        // Update active card and main content based on the *currently visible* cards
        const currentActive = document.querySelector('.account-card.active');
        if (currentActive) {
            currentActive.classList.remove('active');
            currentActive.style.backgroundColor = ''; // Reset background
        }

        if (firstVisibleCard) {
            firstVisibleCard.classList.add('active');
            firstVisibleCard.style.backgroundColor = '#D9D9D9';
            updateMainContent(firstVisibleCard);
        } else {
            // If no accounts are visible after filtering, clear main content
            detailAccountNumber.textContent = 'N/A';
            detailAccountName.textContent = 'N/A';
            detailAccountType.textContent = 'N/A';
            detailBranch.textContent = 'N/A';
            balanceRow.style.display = 'flex'; // Default to showing balance row
            creditCardDetails.style.display = 'none';
            detailAvailableBalance.textContent = formatCurrency(0);
            transactionHistoryList.innerHTML = '<p class="text-muted text-center">No accounts found matching your criteria.</p>';
        }
    }

    // --- Event Listeners for Search and Account Type Filters ---
    accountSearchInput.addEventListener('input', filterAndSearchAccounts);

    applyFiltersBtn.addEventListener('click', function() {
        filterAndSearchAccounts();
        // Close the dropdown after applying filters
        const dropdownElement = document.querySelector('.dropdown-menu.show');
        if (dropdownElement) {
            const bsDropdown = bootstrap.Dropdown.getInstance(dropdownElement.previousElementSibling);
            if (bsDropdown) bsDropdown.hide();
        }
    });

    resetFiltersBtn.addEventListener('click', function() {
        filterCheckboxes.forEach(cb => cb.checked = false);
        accountSearchInput.value = ''; // Clear search input on reset
        filterAndSearchAccounts();
        // Close the dropdown after resetting filters
        const dropdownElement = document.querySelector('.dropdown-menu.show');
        if (dropdownElement) {
            const bsDropdown = bootstrap.Dropdown.getInstance(dropdownElement.previousElementSibling);
            if (bsDropdown) bsDropdown.hide();
        }
    });


    // --- Show/Hide Account Modal Logic ---
    // Add event listeners to the show/hide toggles
    document.querySelectorAll('#showHideAccountModal .form-check-input').forEach(toggle => {
        toggle.addEventListener('change', filterAndSearchAccounts); // Call filterAndSearchAccounts directly
    });

    // Also, ensure filters are applied when the modal is initially shown
    if (showHideAccountModalElement) {
        showHideAccountModalElement.addEventListener('shown.bs.modal', filterAndSearchAccounts);
    }


    // --- Add click event listener to each account card ---
    accountCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove 'active' class from previously active card
            const currentActive = document.querySelector('.account-card.active');
            if (currentActive) {
                currentActive.classList.remove('active');
                currentActive.style.backgroundColor = ''; // Reset to default or remove inline style
            }

            // Add 'active' class to the clicked card
            this.classList.add('active');
            this.style.backgroundColor = '#D9D9D9'; // Set active color

            // Update main content based on clicked card's data
            updateMainContent(this);
        });
    });

    // --- Initialize content with the first active/visible card on page load ---
    // This needs to happen AFTER all filtering logic is set up
    filterAndSearchAccounts(); // Apply all filters (search, type, show/hide) initially

    const initialActiveCard = document.querySelector('.account-card.active');
    if (!initialActiveCard) { // If no active card found after initial filtering
        // Find the very first visible card after filtering and make it active
        const firstTrulyVisibleCard = document.querySelector('.account-card[style*="display: block"], .account-card:not([style*="display: none"])');
        if (firstTrulyVisibleCard) {
            firstTrulyVisibleCard.classList.add('active');
            firstTrulyVisibleCard.style.backgroundColor = '#D9D9D9';
            updateMainContent(firstTrulyVisibleCard);
        }
    }
});
</script>