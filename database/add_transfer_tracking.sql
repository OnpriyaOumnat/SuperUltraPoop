ALTER TABLE payments
    ADD COLUMN transfer_amount DECIMAL(10,2) NULL AFTER amount,
    ADD COLUMN transfer_reference VARCHAR(100) NULL AFTER transfer_amount,
    ADD COLUMN transfer_submitted_at DATETIME NULL AFTER transfer_reference;
