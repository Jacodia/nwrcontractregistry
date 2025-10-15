-- Converted MySQL schema to Microsoft SQL Server (T-SQL)
-- Generated: 15-10-2025
-- Source: schema.txt (MySQL)

-- Create the database if it doesn't exist
IF DB_ID(N'nwr_crdb') IS NULL
BEGIN
    CREATE DATABASE [nwr_crdb];
END
GO

USE [nwr_crdb];
GO

-- =====================================================
-- Table: users
-- =====================================================
IF OBJECT_ID(N'dbo.users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.[users] (
        userid INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        username NVARCHAR(100) NOT NULL,
        email NVARCHAR(200) NOT NULL,
        password NVARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL CONSTRAINT DF_users_role DEFAULT ('viewer'),
        created_at DATETIME2 NOT NULL CONSTRAINT DF_users_created_at DEFAULT (SYSUTCDATETIME())
    );

    CREATE UNIQUE INDEX UX_users_username ON dbo.[users](username);
    CREATE UNIQUE INDEX UX_users_email ON dbo.[users](email);

    -- Enforce allowed values for role (equivalent to MySQL ENUM)
    ALTER TABLE dbo.[users]
    ADD CONSTRAINT CK_users_role_values CHECK (role IN ('admin','viewer','manager'));
END
GO

-- =====================================================
-- Table: contracts
-- =====================================================
IF OBJECT_ID(N'dbo.contracts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.[contracts] (
        contractid INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        parties NVARCHAR(255) NOT NULL,
        typeOfContract NVARCHAR(150) NOT NULL,
        duration NVARCHAR(100) NOT NULL,
        description NVARCHAR(MAX) NULL,
        expiryDate DATE NOT NULL,
        reviewByDate DATE NULL,
        contractValue DECIMAL(15,2) NULL,
        filepath NVARCHAR(255) NULL,
        notify_manager BIT NOT NULL CONSTRAINT DF_contracts_notify_manager DEFAULT ((1)),
        manager_id INT NOT NULL
    );

    ALTER TABLE dbo.[contracts]
    ADD CONSTRAINT FK_contracts_users_manager FOREIGN KEY (manager_id)
        REFERENCES dbo.[users](userid)
        ON DELETE CASCADE;
END
GO

-- =====================================================
-- Table: contractTypes
-- =====================================================
IF OBJECT_ID(N'dbo.contractTypes', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.[contractTypes] (
        typeid INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        name NVARCHAR(150) NOT NULL,
        createdBy INT NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_contractTypes_createdAt DEFAULT (SYSUTCDATETIME())
    );

    CREATE UNIQUE INDEX UQ_contractTypes_name ON dbo.[contractTypes](name);

    ALTER TABLE dbo.[contractTypes]
    ADD CONSTRAINT FK_contractTypes_createdBy FOREIGN KEY (createdBy)
        REFERENCES dbo.[users](userid)
        ON DELETE SET NULL;
END
GO

-- =====================================================
-- Sample data inserts (converted)
-- Note: adjust manager_id values to match actual users in your users table
-- =====================================================
SET IDENTITY_INSERT dbo.[contracts] ON;

INSERT INTO dbo.[contracts] (contractid, parties, typeOfContract, duration, description, expiryDate, reviewByDate, contractValue, manager_id)
VALUES
(1, N'NWR // NBC', N'Barter agreement', N'2 years', NULL, '2024-10-31', '2024-09-01', 78499.00, 2),
(2, N'NWR // Microsoft', N'Office 365', N'12 months', NULL, '2024-11-01', NULL, NULL, 2),
(3, N'NWR // FNB', N'Credit OD Facility', N'One-year', NULL, '2024-12-31', '2024-11-01', 6500000.00, 2),
(4, N'NWR // CIMSO', N'ERP system (Innkeeper)', N'12 months', NULL, '2025-07-31', NULL, 87548.66, 2),
(5, N'NWR // Alliance Media', N'Lease agreement', N'10 years', NULL, '2025-12-31', '2025-10-01', 86940.00, 2),
(6, N'NWR // Ricoh', N'Printers rental', N'36 months', NULL, '2026-03-31', NULL, NULL, 2),
(7, N'NWR // Microsoft', N'Volume licensing', N'36 months', NULL, '2026-03-31', NULL, NULL, 2),
(8, N'NWR // BCX', N'Offsite backup of data', N'12 months', NULL, '2026-08-01', NULL, NULL, 2);

SET IDENTITY_INSERT dbo.[contracts] OFF;
GO

-- contractTypes sample inserts
INSERT INTO dbo.[contractTypes] (name, createdBy)
VALUES
(N'Service Agreement', NULL),
(N'Supply Agreement', NULL),
(N'Employment Contract', NULL),
(N'Lease Agreement', NULL),
(N'Partnership Agreement', NULL),
(N'Other', NULL);
GO

-- Notes:
-- - The INSERTs above assume the referenced user ids (manager_id = 2) already exist in dbo.users.
--   If not, either create matching user rows first or remove/adjust the sample INSERTs.
-- - Date literal format 'YYYY-MM-DD' works in SQL Server for unambiguous dates.
-- - NVARCHAR is used to support Unicode content. Adjust lengths as needed.

-- End of converted schema
