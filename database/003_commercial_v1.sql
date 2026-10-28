ALTER TABLE leads ADD COLUMN IF NOT EXISTS contact_role VARCHAR(160) NULL AFTER email;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS improvement_goal TEXT NULL AFTER team_size;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS next_action_at DATETIME NULL AFTER next_action;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS lost_reason VARCHAR(120) NULL AFTER notes;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS converted_organization_id CHAR(36) NULL AFTER owner_admin_id;
CREATE INDEX IF NOT EXISTS idx_leads_owner ON leads(owner_admin_id);
CREATE INDEX IF NOT EXISTS idx_leads_next_action ON leads(next_action_at);