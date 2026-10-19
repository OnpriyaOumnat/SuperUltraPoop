ALTER TABLE payments
    ADD COLUMN transfer_slip VARCHAR(255) NULL AFTER transfer_reference;
