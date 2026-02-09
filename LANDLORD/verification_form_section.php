<div class="requirements-box">
    <h4><i class="fas fa-clipboard-list"></i> Required Documents for Verification</h4>
    <p>Please prepare and upload the following documents. All documents must be clear, legible, and valid.</p>
</div>

<form method="POST" enctype="multipart/form-data" id="verificationForm">
    <div class="row">
        <!-- Document 1: Valid ID -->
        <div class="col-md-6">
            <div class="document-card">
                <h5>
                    <span class="icon"><i class="fas fa-id-card"></i></span>
                    1. Valid Government ID
                </h5>
                <p>Upload a clear photo of your government-issued identification. Accepted IDs:</p>
                <ul style="font-size: 13px; color: #666;">
                    <li>Philippine Passport</li>
                    <li>Driver's License</li>
                    <li>PhilSys National ID</li>
                    <li>SSS ID / UMID</li>
                    <li>Postal ID</li>
                    <li>Voter's ID</li>
                </ul>
                <div class="file-input-wrapper">
                    <input type="file" name="valid_id" id="valid_id" accept="image/*,.pdf" required>
                    <label for="valid_id" class="file-input-label">
                        <i class="fas fa-upload"></i> Choose ID Document
                    </label>
                    <div class="file-name"></div>
                </div>
            </div>
        </div>

        <!-- Document 2: Proof of Ownership -->
        <div class="col-md-6">
            <div class="document-card">
                <h5>
                    <span class="icon"><i class="fas fa-home"></i></span>
                    2. Proof of Property Ownership
                </h5>
                <p>Legal documentation proving you own the property. Accepted documents:</p>
                <ul style="font-size: 13px; color: #666;">
                    <li>Title Deed (TCT/CCT)</li>
                    <li>Tax Declaration</li>
                    <li>Contract of Sale</li>
                    <li>Property Registration</li>
                </ul>
                <div class="file-input-wrapper">
                    <input type="file" name="proof_of_ownership" id="proof_of_ownership" accept="image/*,.pdf" required>
                    <label for="proof_of_ownership" class="file-input-label">
                        <i class="fas fa-upload"></i> Choose Ownership Document
                    </label>
                    <div class="file-name"></div>
                </div>
            </div>
        </div>

        <!-- Document 3: Landlord Insurance -->
        <div class="col-md-6">
            <div class="document-card">
                <h5>
                    <span class="icon"><i class="fas fa-shield-alt"></i></span>
                    3. Landlord Insurance Policy
                </h5>
                <p>Active insurance policy covering the rental property. Must include:</p>
                <ul style="font-size: 13px; color: #666;">
                    <li>Policy number and validity</li>
                    <li>Property address</li>
                    <li>Coverage details</li>
                    <li>Landlord liability coverage</li>
                </ul>
                <div class="file-input-wrapper">
                    <input type="file" name="landlord_insurance" id="landlord_insurance" accept="image/*,.pdf" required>
                    <label for="landlord_insurance" class="file-input-label">
                        <i class="fas fa-upload"></i> Choose Insurance Document
                    </label>
                    <div class="file-name"></div>
                </div>
            </div>
        </div>

        <!-- Document 4: Gas Safety Certificate -->
        <div class="col-md-6">
            <div class="document-card">
                <h5>
                    <span class="icon"><i class="fas fa-fire"></i></span>
                    4. Gas Safety Certificate
                </h5>
                <p>Current gas safety inspection certificate (if property has gas appliances):</p>
                <ul style="font-size: 13px; color: #666;">
                    <li>Must be less than 12 months old</li>
                    <li>From certified gas engineer</li>
                    <li>Covers all gas appliances</li>
                    <li>Shows property address</li>
                </ul>
                <div class="file-input-wrapper">
                    <input type="file" name="gas_safety_cert" id="gas_safety_cert" accept="image/*,.pdf" required>
                    <label for="gas_safety_cert" class="file-input-label">
                        <i class="fas fa-upload"></i> Choose Gas Certificate
                    </label>
                    <div class="file-name"></div>
                </div>
                <small class="text-muted"><i class="fas fa-info-circle"></i> If no gas appliances, upload a signed declaration stating "No gas appliances on property"</small>
            </div>
        </div>

        <!-- Document 5: Electrical Safety Certificate -->
        <div class="col-md-6">
            <div class="document-card">
                <h5>
                    <span class="icon"><i class="fas fa-bolt"></i></span>
                    5. Electrical Safety Certificate
                </h5>
                <p>Electrical Installation Condition Report (EICR):</p>
                <ul style="font-size: 13px; color: #666;">
                    <li>Issued by licensed electrician</li>
                    <li>Must be less than 5 years old</li>
                    <li>All electrical systems inspected</li>
                    <li>No major defects reported</li>
                </ul>
                <div class="file-input-wrapper">
                    <input type="file" name="electric_safety_cert" id="electric_safety_cert" accept="image/*,.pdf" required>
                    <label for="electric_safety_cert" class="file-input-label">
                        <i class="fas fa-upload"></i> Choose Electrical Certificate
                    </label>
                    <div class="file-name"></div>
                </div>
            </div>
        </div>

        <!-- Document 6: Lease Agreement -->
        <div class="col-md-6">
            <div class="document-card">
                <h5>
                    <span class="icon"><i class="fas fa-file-contract"></i></span>
                    6. Lease Agreement Template
                </h5>
                <p>Upload your draft lease agreement that complies with local laws:</p>
                <ul style="font-size: 13px; color: #666;">
                    <li>Legally compliant terms</li>
                    <li>Clear rental amount & duration</li>
                    <li>Deposit & payment terms</li>
                    <li>Rights & responsibilities</li>
                </ul>
                <div class="file-input-wrapper">
                    <input type="file" name="lease_agreement" id="lease_agreement" accept=".pdf,.doc,.docx" required>
                    <label for="lease_agreement" class="file-input-label">
                        <i class="fas fa-upload"></i> Choose Lease Template
                    </label>
                    <div class="file-name"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning mt-4" role="alert">
        <h6><i class="fas fa-exclamation-triangle"></i> Important Reminders:</h6>
        <ul class="mb-0" style="font-size: 14px;">
            <li>All documents must be clear and legible</li>
            <li>Maximum file size: 10MB per document</li>
            <li>Accepted formats: JPG, PNG, PDF, DOC, DOCX</li>
            <li>Ensure all personal information is visible and not blurred</li>
            <li>Documents must be current and not expired</li>
        </ul>
    </div>

    <button class="main-button mt-4 w-100" type="submit" name="submit_documents" style="padding: 15px; font-size: 18px;">
        <i class="fas fa-paper-plane"></i> Submit All Documents for Verification
    </button>
</form>