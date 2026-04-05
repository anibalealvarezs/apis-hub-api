# APIs Hub API Client: Sync & Reliability Plan

This document tracks the updates needed for the `apis-hub-api` client library to ensure it remains compatible with the hardened APIs Hub server infrastructure.

## 📋 Status Checklist

### ⚙️ Phase 1: Error Handling & Management (Linked to Server Phase 4 & 5) ✅

- [x] **Task 1.1**: Update the HTTP Client to handle standardized JSON error responses (422, 400, 500).
- [x] **Task 1.2**: Implement `containerAction` method for remote infrastructure control.
- [x] **Task 1.3**: Audit all API calls to ensure they include required headers (e.g., `Accept: application/json`).
- [x] **✅ Validation**: PASS (Verified via Facade integration tests).

### 🌐 Phase 2: Public API Integration (Linked to Server Phase 6) ⏳

- [ ] **Task 2.1**: Implement the new **Public API Key** authentication method in the client's constructor.
- [ ] **Task 2.2**: Update the Base URL discovery logic to support the new `*.server.apis-hub.cloud` pattern.
- [ ] **Task 2.3**: Build specialized methods for the new `/api/v1/public` endpoints.

### 🧪 Phase 3: Reliability & Logging

- [ ] **Task 3.1**: Implement more verbose logging for failed API requests (without leaking secrets).
- [ ] **Task 3.2**: Add Retry/Backoff logic for intermittent network or server issues.

---

### 🔗 Integration Notes

- This project must remain synchronized with the **APIs Hub Security Hardening Plan** located in the core server repositories. Any change to the server response format MUST be reflected here immediately.
