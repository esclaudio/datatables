CREATE TABLE "users" (
    "id" INTEGER PRIMARY KEY,
    "name" TEXT
);

CREATE TABLE "customers" (
    "id" INTEGER PRIMARY KEY,
    "name" TEXT,
    "age" INTEGER,
    "created_by" INTEGER
);

INSERT INTO "users" ("id", "name") VALUES
(1, "Administrator"),
(2, "Claudio");

INSERT INTO "customers" ("id", "name", "age", "created_by") VALUES
(1, 'One Inc.', 20, 1),
(2, 'Two Inc.', 22, 2),
(3, 'Three Inc.', 24, 1),
(4, 'Four Inc.', 26, 1),
(5, 'Five Inc.', 28, 1),
(6, 'Six Inc.', 20, 1),
(7, 'Seven Inc.', 22, 2),
(8, 'Eight Inc.', 24, 1),
(9, 'Nine Inc.', 26, 1),
(10, 'Ten Inc.', 28, 2),
(11, 'Eleven Inc.', 30, 2);

