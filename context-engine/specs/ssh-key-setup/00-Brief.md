# SSH Key Setup for Hetzner - Strategic Brief

## 1. Strategic Intent

**Goal:** Generate a secure SSH key pair and provide instructions for its installation on a remote Hetzner server to enable secure, passwordless access.

**Success Verdict:** 
- [ ] User can successfully log in to the remote Hetzner server using the generated private key without being prompted for a password.
- [ ] The public key is correctly formatted for use in Hetzner's Cloud Console or `.ssh/authorized_keys` file.

## 2. The Claims (Features)

Each Claim is a capability the system must have. A Claim is not "done" until its Verdict passes.

| Claim ID | Description | Verdict (Test) |
|----------|-------------|----------------|
| CLAIM-01 | Generate a secure Ed25519 SSH key pair. | `ls` shows private and public key files; `ssh-keygen -l` confirms type. |
| CLAIM-02 | Provide clear instructions for adding the public key to Hetzner. | User confirms instructions are actionable. |
| CLAIM-03 | Verify successful passwordless login. | User reports successful login. |

## 3. The Elements (Required Components)

| Element | Purpose | Belongs To Claim |
|---------|---------|------------------|
| Private Key | To be kept by the user for authentication. | CLAIM-01 |
| Public Key | To be uploaded to the server/Hetzner dashboard. | CLAIM-01 |
| Installation Guide | Step-by-step instructions for the user. | CLAIM-02 |

## 4. The Evidence (Inputs & Constraints)

**Tech Stack:** SSH (OpenSSH), Ed25519 algorithm.

**External APIs:** Hetzner Cloud Console (UI interaction).

## 5. Existing Infrastructure (CRITICAL for Brownfield Projects)

> ⚠️ No existing server-side configuration is known yet.

### Known Constraints
- User must have physical/emergency access to the server if something goes wrong (e.g., via Hetzner Console).

## 6. Pre-Mortem (Risk Assessment)

**What could break?**
- User might accidentally lock themselves out if password auth is disabled before the key is verified.

**What assumptions are we making?**
- The server is running a standard Linux distribution with OpenSSH installed.

**What do we NOT know yet?**
- Server IP address or username (typically `root` for new VPS).

## 7. Approval Gate

**Status:** [x] DRAFT  [ ] APPROVED

**Approved By:** 

**Date:** 2026-03-14

---

> ⚠️ **EXIT CONDITION:** This Brief is not approved until the user confirms the Success Verdict.
