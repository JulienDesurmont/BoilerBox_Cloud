USE ipc_master1
ALTER TABLE t_donnee DROP primary key, ADD primary key(id,horodatage);

ALTER table t_donnee PARTITION BY RANGE(Year(horodatage)) 
SUBPARTITION BY HASH (Month(horodatage)) SUBPARTITIONS 12
(PARTITION p1 VALUES LESS THAN(2018),
PARTITION p2 VALUES LESS THAN(2019),
PARTITION p3 VALUES LESS THAN(2020),
PARTITION p4 VALUES LESS THAN(2021),
PARTITION p5 VALUES LESS THAN(2022),
PARTITION p6 VALUES LESS THAN(2023),
PARTITION p7 VALUES LESS THAN(2024),
PARTITION p8 VALUES LESS THAN(2025),
PARTITION p9 VALUES LESS THAN(2026),
PARTITION p10 VALUES LESS THAN(2027),
PARTITION p11 VALUES LESS THAN(2028),
PARTITION p12 VALUES LESS THAN(2029),
PARTITION p13 VALUES LESS THAN(2030),
PARTITION p14 VALUES LESS THAN(2031),
PARTITION p15 VALUES LESS THAN(2032),
PARTITION p16 VALUES LESS THAN(2033),
PARTITION p17 VALUES LESS THAN(2034),
PARTITION p18 VALUES LESS THAN(2035),
PARTITION p19 VALUES LESS THAN(2036),
PARTITION p20 VALUES LESS THAN(2037),
PARTITION p21 VALUES LESS THAN(2038),
PARTITION p22 VALUES LESS THAN(2039),
PARTITION p23 VALUES LESS THAN(2040),
PARTITION p24 VALUES LESS THAN(2041),
PARTITION p25 VALUES LESS THAN(2042),
PARTITION p26 VALUES LESS THAN(2043),
PARTITION p27 VALUES LESS THAN(2044),
PARTITION p28 VALUES LESS THAN(2045),
PARTITION p29 VALUES LESS THAN(2046),
PARTITION p30 VALUES LESS THAN(2047),
PARTITION p31 VALUES LESS THAN(2048),
PARTITION p32 VALUES LESS THAN(2049),
PARTITION p33 VALUES LESS THAN(2050),
PARTITION p34 VALUES LESS THAN(2051),
PARTITION p35 VALUES LESS THAN(2052),
PARTITION p36 VALUES LESS THAN(2053),
PARTITION p37 VALUES LESS THAN MAXVALUE
);

