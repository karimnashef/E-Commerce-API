CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,          
    user_id uuid NOT NULL,         
    title VARCHAR(255) NOT NULL,        
    body TEXT NOT NULL,                 
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT now(),
    updated_at TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT fk_user
        FOREIGN KEY(user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

ALTER TABLE notifications
ALTER COLUMN user_id TYPE bigint USING user_id::bigint;

ALTER TABLE notifications
DROP COLUMN user_id;

ALTER TABLE notifications
ADD COLUMN user_id bigint;