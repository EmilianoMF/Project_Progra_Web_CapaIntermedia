DELIMITER $$

CREATE PROCEDURE sp_insertar_usuario (
    IN p_nombre_completo VARCHAR(100),
    IN p_fecha_nacimiento DATE,
    IN p_foto LONGBLOB,
    IN p_genero ENUM('Male','Female','Otro'),
    IN p_genero_personalizado VARCHAR(50),
    IN p_pais_nacimiento VARCHAR(50),
    IN p_nacionalidad VARCHAR(50),
    IN p_correo VARCHAR(100),
    IN p_contrasena VARCHAR(255)
)
BEGIN
    INSERT INTO usuarios (
        nombre_completo, fecha_nacimiento, foto, genero, genero_personalizado,
        pais_nacimiento, nacionalidad, correo, contrasena, rol
    ) VALUES (
        p_nombre_completo, p_fecha_nacimiento, p_foto, p_genero, p_genero_personalizado,
        p_pais_nacimiento, p_nacionalidad, p_correo, p_contrasena, 'Usuario'
    );
END$$


DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_login_usuario (
    IN p_correo VARCHAR(100)
)
BEGIN
    SELECT 
        id_usuario,
        nombre_completo,
        correo,
        contrasena,
        rol,
        foto
    FROM usuarios
    WHERE correo = p_correo
    LIMIT 1;
END$$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_actualizar_usuario (

    IN p_id_usuario INT,
    IN p_nombre_completo VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_nacionalidad VARCHAR(50),
    IN p_contrasena VARCHAR(255),
    IN p_foto LONGBLOB

)

BEGIN

    UPDATE usuarios
    SET

        nombre_completo = p_nombre_completo,
        correo = p_correo,
        nacionalidad = p_nacionalidad,

        contrasena =
        CASE
            WHEN p_contrasena IS NOT NULL
            THEN p_contrasena
            ELSE contrasena
        END,

        foto =
        CASE
            WHEN p_foto IS NOT NULL
            THEN p_foto
            ELSE foto
        END

    WHERE id_usuario = p_id_usuario;

END$$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_crear_mundial(

    IN p_nombre VARCHAR(100),
    IN p_anio YEAR,
    IN p_sede VARCHAR(100),
    IN p_logotipo LONGBLOB,
    IN p_imagen LONGBLOB,
    IN p_resena TEXT

)
BEGIN

    INSERT INTO mundiales(

        nombre,
        anio,
        sede,
        logotipo,
        imagen,
        reseña

    )
    VALUES(

        p_nombre,
        p_anio,
        p_sede,
        p_logotipo,
        p_imagen,
        p_resena

    );

END$$

DELIMITER ;


-- ===========================
-- PROCEDURE: Crear publicación
-- ===========================
DELIMITER $$
CREATE PROCEDURE sp_crear_publicacion(
    IN p_id_usuario   INT,
    IN p_id_mundial   INT,
    IN p_id_categoria INT,
    IN p_seleccion    VARCHAR(100),
    IN p_descripcion  TEXT
)
BEGIN
    INSERT INTO publicaciones(
        id_usuario,
        id_mundial,
        id_categoria,
        seleccion,
        descripcion,
        fecha_elaboracion,
        aprobado
    )
    VALUES(
        p_id_usuario,
        p_id_mundial,
        p_id_categoria,
        p_seleccion,
        p_descripcion,
        NOW(),
        FALSE
    );

    -- Retorna el id generado para usarlo en PHP
    SELECT LAST_INSERT_ID() AS id_publicacion;
END$$
DELIMITER ;

-- ===========================
-- PROCEDURE: Crear archivo de publicación
-- ===========================
DELIMITER $$
CREATE PROCEDURE sp_crear_archivo_publicacion(
    IN p_id_publicacion INT,
    IN p_contenido      LONGBLOB,
    IN p_tipo           ENUM('imagen','video'),
    IN p_orden          INT
)
BEGIN
    INSERT INTO publicacion_archivos(
        id_publicacion,
        contenido,
        tipo,
        orden
    )
    VALUES(
        p_id_publicacion,
        p_contenido,
        p_tipo,
        p_orden
    );
END$$
DELIMITER ;

-- ===========================
-- APROBAR publicación
-- ===========================
DELIMITER $$
CREATE PROCEDURE sp_aprobar_publicacion(
    IN p_id_publicacion INT
)
BEGIN
    UPDATE publicaciones
    SET
        aprobado        = TRUE,
        fecha_aprobacion = NOW()
    WHERE id_publicacion = p_id_publicacion;
END$$
DELIMITER ;



DELIMITER $$
CREATE PROCEDURE sp_rechazar_publicacion(
    IN p_id_publicacion INT
)
BEGIN
    UPDATE publicaciones
    SET rechazado = TRUE
    WHERE id_publicacion = p_id_publicacion;
END$$
DELIMITER ;


DELIMITER $$

-- ====================================================
-- PROCEDURE: Registrar Vista
-- Crea el registro de estadísticas si no existe, 
-- o suma 1 vista si ya existe.
-- ====================================================
CREATE PROCEDURE sp_registrar_vista(
    IN p_id_publicacion INT
)
BEGIN
    DECLARE v_existe INT;

    SELECT COUNT(*) INTO v_existe 
    FROM estadisticas 
    WHERE id_publicacion = p_id_publicacion;

    IF v_existe = 0 THEN
        INSERT INTO estadisticas (id_publicacion, vistas, likes) 
        VALUES (p_id_publicacion, 1, 0);
    ELSE
        UPDATE estadisticas 
        SET vistas = vistas + 1 
        WHERE id_publicacion = p_id_publicacion;
    END IF;
END$$

-- ====================================================
-- PROCEDURE: Toggle Like (Dar o Quitar Like)
-- Verifica si el usuario ya dio like. Si sí, lo quita.
-- Si no, lo agrega. También actualiza las estadísticas.
-- ====================================================
CREATE PROCEDURE sp_toggle_like(
    IN p_id_publicacion INT,
    IN p_id_usuario INT
)
BEGIN
    DECLARE v_existe INT;

    -- Verificar si ya existe el like
    SELECT COUNT(*) INTO v_existe 
    FROM interacciones 
    WHERE id_publicacion = p_id_publicacion 
      AND id_usuario = p_id_usuario 
      AND tipo = 'Like';

    IF v_existe > 0 THEN
        -- Quitar Like
        DELETE FROM interacciones 
        WHERE id_publicacion = p_id_publicacion 
          AND id_usuario = p_id_usuario 
          AND tipo = 'Like';

        -- Restar like en estadísticas (evitando números negativos)
        UPDATE estadisticas 
        SET likes = GREATEST(likes - 1, 0) 
        WHERE id_publicacion = p_id_publicacion;
    ELSE
        -- Dar Like
        INSERT INTO interacciones (id_publicacion, id_usuario, tipo) 
        VALUES (p_id_publicacion, p_id_usuario, 'Like');

        -- Sumar like en estadísticas (asegurando que exista el registro primero)
        CALL sp_registrar_vista(p_id_publicacion); -- Crea el registro si por algún motivo no existe
        
        UPDATE estadisticas 
        SET vistas = vistas - 1, -- Compensamos la vista que agregó el sp anterior
            likes = likes + 1 
        WHERE id_publicacion = p_id_publicacion;
    END IF;
END$$

-- ====================================================
-- PROCEDURE: Agregar Comentario
-- ====================================================
CREATE PROCEDURE sp_agregar_comentario(
    IN p_id_publicacion INT,
    IN p_id_usuario INT,
    IN p_comentario TEXT
)
BEGIN
    INSERT INTO interacciones (id_publicacion, id_usuario, tipo, comentario) 
    VALUES (p_id_publicacion, p_id_usuario, 'Comentario', p_comentario);
END$$

DELIMITER ;

DELIMITER $$

-- 1. PROCEDIMIENTO PARA OBTENER LOS DETALLES DE UN MUNDIAL ESPECÍFICO
CREATE PROCEDURE sp_obtener_mundial_detalle(
    IN p_id_mundial INT
)
BEGIN
    SELECT * FROM mundiales WHERE id_mundial = p_id_mundial;
END$$


-- 2. PROCEDIMIENTO PARA FILTRAR Y ORDENAR PUBLICACIONES DENTRO DE UN MUNDIAL
CREATE PROCEDURE sp_filtrar_publicaciones_mundial(
    IN p_id_mundial INT,
    IN p_id_categoria INT,
    IN p_seleccion VARCHAR(100),
    IN p_orden VARCHAR(20)
)
BEGIN
    -- Se construye consulta dinámica para evitar problemas de tipos en el ORDER BY dinámico
    SET @sql = "
        SELECT
            p.id_publicacion,
            p.descripcion,
            p.seleccion,
            p.fecha_aprobacion,
            u.nombre_completo,
            c.nombre_categoria,
            COALESCE(e.vistas, 0) AS vistas,
            COALESCE(e.likes, 0) AS likes,
            (SELECT COUNT(*) FROM publicacion_archivos a WHERE a.id_publicacion = p.id_publicacion) AS total_archivos,
            (SELECT a.id_archivo FROM publicacion_archivos a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.orden LIMIT 1) AS id_primer_archivo,
            (SELECT a.tipo FROM publicacion_archivos a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.orden LIMIT 1) AS tipo_primer_archivo
        FROM publicaciones p
        JOIN usuarios u ON u.id_usuario = p.id_usuario
        JOIN categorias c ON c.id_categoria = p.id_categoria
        LEFT JOIN estadisticas e ON e.id_publicacion = p.id_publicacion
        WHERE p.id_mundial = ? AND p.aprobado = TRUE AND p.rechazado = FALSE
    ";
    
    -- Filtros condicionales
    IF p_id_categoria IS NOT NULL AND p_id_categoria > 0 THEN
        SET @sql = CONCAT(@sql, " AND p.id_categoria = ", p_id_categoria);
    END IF;
    
    IF p_seleccion IS NOT NULL AND p_seleccion != '' THEN
        SET @sql = CONCAT(@sql, " AND p.seleccion LIKE ", QUOTE(CONCAT('%', p_seleccion, '%')));
    END IF;
    
    -- Orden dinámico
    IF p_orden = 'antiguas' THEN
        SET @sql = CONCAT(@sql, " ORDER BY p.fecha_aprobacion ASC");
    ELSEIF p_orden = 'populares' THEN
        SET @sql = CONCAT(@sql, " ORDER BY vistas DESC, likes DESC, p.fecha_aprobacion DESC");
    ELSE
        SET @sql = CONCAT(@sql, " ORDER BY p.fecha_aprobacion DESC");
    END IF;
    
    -- Preparación y ejecución segura
    SET @id_m = p_id_mundial;
    PREPARE stmt FROM @sql;
    EXECUTE stmt USING @id_m;
    DEALLOCATE PREPARE stmt;
END$$


-- 3. PROCEDIMIENTO PARA EL BUSCADOR AVANZADO GLOBAL
CREATE PROCEDURE sp_buscar_publicaciones_avanzado(
    IN p_id_categoria INT,
    IN p_anio INT,
    IN p_sede VARCHAR(100),
    IN p_usuario VARCHAR(100)
)
BEGIN
    SET @sql = "
        SELECT
            p.id_publicacion,
            p.descripcion,
            p.seleccion,
            p.fecha_aprobacion,
            u.nombre_completo,
            c.nombre_categoria,
            m.nombre AS nombre_mundial,
            m.anio,
            m.sede,
            COALESCE(e.vistas, 0) AS vistas,
            COALESCE(e.likes, 0) AS likes,
            (SELECT COUNT(*) FROM publicacion_archivos a WHERE a.id_publicacion = p.id_publicacion) AS total_archivos,
            (SELECT a.id_archivo FROM publicacion_archivos a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.orden LIMIT 1) AS id_primer_archivo,
            (SELECT a.tipo FROM publicacion_archivos a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.orden LIMIT 1) AS tipo_primer_archivo
        FROM publicaciones p
        JOIN usuarios u ON u.id_usuario = p.id_usuario
        JOIN categorias c ON c.id_categoria = p.id_categoria
        JOIN mundiales m ON m.id_mundial = p.id_mundial
        LEFT JOIN estadisticas e ON e.id_publicacion = p.id_publicacion
        WHERE p.aprobado = TRUE AND p.rechazado = FALSE
    ";
    
    IF p_id_categoria IS NOT NULL AND p_id_categoria > 0 THEN
        SET @sql = CONCAT(@sql, " AND p.id_categoria = ", p_id_categoria);
    END IF;
    
    IF p_anio IS NOT NULL AND p_anio > 0 THEN
        SET @sql = CONCAT(@sql, " AND m.anio = ", p_anio);
    END IF;
    
    IF p_sede IS NOT NULL AND p_sede != '' THEN
        SET @sql = CONCAT(@sql, " AND m.sede = ", QUOTE(p_sede));
    END IF;
    
    IF p_usuario IS NOT NULL AND p_usuario != '' THEN
        SET @sql = CONCAT(@sql, " AND u.nombre_completo LIKE ", QUOTE(CONCAT('%', p_usuario, '%')));
    END IF;
    
    SET @sql = CONCAT(@sql, " ORDER BY p.fecha_aprobacion DESC");
    
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

DELIMITER ;

DELETE 
FROM usuarios
where id_usuario=27;


SELECT *
FROM usuarios;


