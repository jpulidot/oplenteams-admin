INSERT IGNORE INTO admin_roles (code,name,description,is_system) VALUES
('super_admin','Super Admin','Full Oplen backoffice access',1),
('commercial','Commercial','Leads, pipeline, demos and organizations',1),
('support','Support','Organizations, users and account support',1),
('finance','Finance','Subscriptions, billing and revenue',1);

INSERT IGNORE INTO permissions (code,name) VALUES
('admin.full','Full administration'),
('commercial.read','Read commercial data'),
('commercial.write','Manage commercial data'),
('organizations.read','Read organizations'),
('organizations.write','Manage organizations'),
('users.read','Read customer accounts'),
('users.write','Manage customer accounts'),
('team.read','Read Oplen internal team'),
('team.write','Manage Oplen internal team'),
('billing.read','Read billing'),
('billing.write','Manage billing'),
('system.read','Read system status and audit logs');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM admin_roles r CROSS JOIN permissions p WHERE r.code='super_admin';

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM admin_roles r JOIN permissions p ON p.code IN ('commercial.read','commercial.write','organizations.read') WHERE r.code='commercial';

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM admin_roles r JOIN permissions p ON p.code IN ('organizations.read','users.read','users.write') WHERE r.code='support';

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM admin_roles r JOIN permissions p ON p.code IN ('organizations.read','billing.read','billing.write') WHERE r.code='finance';
