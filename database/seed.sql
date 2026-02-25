-- ============================================================
--  MELODY MASTERS — SEED DATA
--  Run this after melody_masters_db.sql to populate the store
-- ============================================================

USE melody_masters_db;

-- ============================================================
-- CATEGORIES
-- ============================================================
INSERT IGNORE INTO categories (id, name, parent_id) VALUES
  (1, 'Guitars',      NULL),
  (2, 'Keyboards',    NULL),
  (3, 'Drums',        NULL),
  (4, 'Cymbals',      NULL),
  (5, 'Violins',      NULL),
  (6, 'Flutes',       NULL),
  (7, 'Accessories',  NULL);

-- ============================================================
-- PRODUCTS  (image column = filename only, no path)
-- ============================================================
INSERT INTO products (category_id, name, price, stock, type, image, description) VALUES

-- GUITARS
(1, 'Fender Player Stratocaster',    699.99,  12, 'physical', 'guitar02.png',
 'The Player Stratocaster brings classic Fender tone and feel to a modern market. Features an alder body, maple neck, and three Player Series Alnico 5 Strat single-coil pickups for bright, clear sound perfect for any style.'),

(1, 'Gibson Les Paul Standard 50s',  2499.00,  6, 'physical', 'guitar03.png',
 'Inspired by the legendary 1950s originals, this Les Paul Standard delivers warm, fat tones from its Burstbucker 1 & 2 pickups. Mahogany body with maple cap, slim taper neck profile, and Kluson-style tuners.'),

(1, 'Epiphone Casino Hollow Body',    599.00, 10, 'physical', 'guitar04.png',
 'Favoured by the Beatles and countless blues legends, the Casino is a fully hollow thinline electric. P-90 style single-coil Dogear pickups deliver a sharp, cutting mid-range bite that cuts through any mix.'),

-- KEYBOARDS
(2, 'Nord Stage 4 Compact',          2799.00,  4, 'physical', 'keyboard01.png',
 'The Nord Stage 4 Compact is a world-class performance keyboard featuring the renowned Nord Piano 5 Engine, Nord Lead A1 Synth, and Nord C2D Organ section. Seamless transitions, powerful effects, and 73 semi-weighted keys.'),

(2, 'Roland FP-90X Digital Piano',   1699.00,  8, 'physical', 'keyboard02.png',
 'Rolands finest portable piano. The FP-90X features the acclaimed PHA-50 keyboard with ivory feel and escapement, SuperNATURAL Piano Modeling, and 88 weighted keys — the most authentic piano experience outside of a grand.'),

-- DRUMS
(3, 'DW Performance Series Kit',     2199.00,  5, 'physical', 'drum01.png',
 'DW''s Performance Series drum kit delivers professional quality at a serious price point. Poplar shells with a 7-ply formula offer a full, focused tone with excellent projection. Includes 10" tom, 12" tom, 14" floor tom, and 22" kick.'),

(3, 'Pearl Session Studio Select',   1349.00,  7, 'physical', 'drum02.png',
 'Crafted with a 6-ply Birch/Basswood shell formula, the Session Studio Select produces a warm, well-rounded tone with enhanced attack. Ideal for studio sessions and live performances that demand consistent quality.'),

(3, 'Tama Starclassic Walnut/Birch', 1899.00,  3, 'physical', 'drum03.png',
 'Walnut outer plies married with birch inner plies create a uniquely warm, complex, and well-defined tone. The Starclassic Walnut/Birch is Tama''s premium offering for the discerning drummer.'),

(3, 'Ludwig Classic Maple Kit',      2599.00,  4, 'physical', 'drum04.png',
 'The drum kit that defined rock and roll. Ludwig''s Classic Maple shells have the same construction used on recordings that shaped popular music. Six-ply maple shells, chrome hardware, and a timeless look.'),

-- CYMBALS
(4, 'Zildjian A Custom Cymbal Set',   699.00,  9, 'physical', 'Cymbol01.png',
 'The A Custom line offers brilliant finish cymbals with a focused, cutting sound. This set includes 14" hi-hats, 16" crash, 18" crash, and 20" medium ride — perfect for rock, pop, and studio work.'),

(4, 'Sabian HHX Evolution Set',       849.00,  6, 'physical', 'Cymbol02.png',
 'Sabian''s HHX Evolution series delivers modern, complex tones with an explosive character. Brilliant finish with hand-hammering that creates a raw, earthy feel alongside a brilliant shimmer. Includes hi-hat, crash, and ride.'),

-- VIOLINS
(5, 'Stentor Student II Violin 4/4',  149.00, 20, 'physical', 'violin01.png',
 'The most popular student violin in the world. The Stentor Student II features a solid carved tonewood top, well-shaped bow, rosin, and a lightweight case. An ideal first instrument for aspiring string players.'),

(5, 'Yamaha V20G Intermediate Violin', 499.00, 10, 'physical', 'violin02.png',
 'Stepping up from beginner instruments, the V20G features a hand-carved spruce top and maple back and sides, with a distinctive reddish-brown finish. A quality bow and lightweight case are included.'),

(5, 'Mendini MV500 Ebony Violin',     229.00, 14, 'physical', 'violin03.png',
 'The MV500 is fitted with all-ebony fittings — pegs, fingerboard, tailpiece, and chinrest — for a more refined aesthetic and sound. The solid spruce top and maple sides produce a clear, resonant tone.'),

-- FLUTES
(6, 'Yamaha YFL-222 Student Flute',   299.00, 15, 'physical', 'flute01.png',
 'The YFL-222 is a key choice for beginners and school players. Its silver-plated body and precise key mechanism make tone production effortless. The pointed arm G key provides a natural and secure playing position.'),

(6, 'Jupiter JFL700A Intermediate Flute', 599.00, 8, 'physical', 'flute02.png',
 'Designed for advancing students, the JFL700A features a solid sterling silver head joint that significantly improves response, tone colour, and projection. Offset G key and split E mechanism for added ease of play.'),

-- ACCESSORIES
(7, 'Vic Firth American Classic 5A Drumsticks', 12.99, 100, 'physical', 'Acce-BrownDrumStick.png',
 'The world''s best-selling drumstick. Hickory construction with nylon tip for a brighter sound and longer life. Perfect for any style at any volume. Standard 5A dimensions for versatile, balanced playing.'),

(7, 'Elixir Nanoweb Guitar Strings 10-46',      14.99, 80, 'physical', 'Acce-GuitarStrings01.png',
 'Elixir''s NANOWEB coating surrounds the entire string — not just the windings — delivering an extended lifespan without sacrificing bright tone. A favourite of touring and recording guitarists worldwide.'),

(7, 'D''Addario EXL110 Nickel Wound Set',        8.99, 120, 'physical', 'Acce-GuitarStrings02.png',
 'The most popular string set in the world. Nickel wound with a plain steel high E and B. Regular 10-46 gauge suits any playing style from light fingerpicking to heavy strumming. Consistent quality every time.'),

(7, 'Vic Firth Stick Bag SBAG2',                 29.99, 40, 'physical', 'Best stick bag Vic Firth SBAG2.png',
 'Professional quality stick bag featuring 8 stick pockets, 2 accessory pockets, and quick-release shoulder strap. Made from durable polyester with reinforced stitching — holds everything a working drummer needs.'),

(7, 'Mike Portnoy Percussion Kit',              349.00, 12, 'physical', 'Acce-MikePortnoy PercussionKit.png',
 'Designed in collaboration with Dream Theater''s Mike Portnoy, this signature percussion kit includes a range of exotic percussion instruments used on his legendary recordings. A unique collector''s piece for the serious percussionist.'),

(7, 'ROC-N-SOC Nitro Drum Throne',              149.00, 18, 'physical', 'Acce-ROC-N-SOC Nitro.png',
 'The ROC-N-SOC Nitro is the industry-standard drum throne for professional drummers. Gas-spring height adjustment with a locking collar, round cushioned top, and solid round base provide comfort and stability for any length of performance.');
