
-- TapSole V0.7
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS shoe_library (
  id INT AUTO_INCREMENT PRIMARY KEY,
  brand VARCHAR(80) NOT NULL,
  model VARCHAR(160) NOT NULL,
  category VARCHAR(80) NULL,
  default_target_km DECIMAL(8,1) NOT NULL DEFAULT 500.0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_shoe_library_brand_model (brand,model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO shoe_library(brand,model,category,default_target_km) VALUES
('adidas','Adizero Adios Pro 4','Race',500),
('ASICS','Superblast 3','Daily / Long Run',700),
('On','Cloudmonster 3','Daily / Long Run',650),
('On','Cloudboom Strike','Race',500),
('PUMA','Fast-R Nitro Elite 3','Race',500),
('Saucony','Endorphin Speed 5','Speed / Daily',650),
('Nike','Alphafly 3','Race',500),
('Nike','Vaporfly 4','Race',500),
('New Balance','FuelCell SuperComp Elite v5','Race',500),
('361°','Flame 3.5','Race',500);

ALTER TABLE user_shoes
  ADD COLUMN IF NOT EXISTS shoe_library_id INT NULL AFTER user_id,
  ADD COLUMN IF NOT EXISTS retired_at DATETIME NULL AFTER active;
