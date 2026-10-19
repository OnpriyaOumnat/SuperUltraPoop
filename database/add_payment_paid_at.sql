ALTER TABLE payments
    ADD COLUMN paid_at DATETIME NULL AFTER transfer_submitted_at;
