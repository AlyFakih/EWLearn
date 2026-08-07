import os
import requests
import mysql.connector


PROJECT_ROOT = os.path.abspath(
    os.path.join(os.path.dirname(__file__), "..")
)


IMAGE_ROOT = os.path.join(
    PROJECT_ROOT,
    "frontend",
    "images",
    "users"
)


DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "student_management"
}


def create_folders():

    folders = [
        "admin",
        "instructors",
        "students"
    ]

    for folder in folders:

        path = os.path.join(
            IMAGE_ROOT,
            folder
        )

        os.makedirs(
            path,
            exist_ok=True
        )


def download_image(url, path):

    response = requests.get(url)

    response.raise_for_status()

    with open(path, "wb") as file:

        file.write(response.content)



def main():

    create_folders()


    db = mysql.connector.connect(**DB_CONFIG)

    cursor = db.cursor(dictionary=True)


    cursor.execute(
        """
        SELECT id, fullName, role, gender
        FROM users
        ORDER BY id
        """
    )


    users = cursor.fetchall()


    admin_count = 1
    instructor_count = 1
    student_count = 1


    for user in users:


        if user["role"] == "admin":

            filename = "admin01.jpg"

            folder = "admin"

            image_number = admin_count

            admin_count += 1


        elif user["role"] == "instructor":

            filename = (
                f"instructor{instructor_count:02}.jpg"
            )

            folder = "instructors"

            image_number = instructor_count

            instructor_count += 1


        else:

            filename = (
                f"student{student_count:02}.jpg"
            )

            folder = "students"

            image_number = student_count

            student_count += 1



        gender = "men"

        if user["gender"].lower() == "female":

            gender = "women"



        url = (
            f"https://randomuser.me/api/portraits/"
            f"{gender}/{image_number}.jpg"
        )


        save_path = os.path.join(
            IMAGE_ROOT,
            folder,
            filename
        )


        print(
            "Downloading:",
            user["fullName"],
            "=>",
            filename
        )


        download_image(
            url,
            save_path
        )


        database_path = (
            f"users/{folder}/{filename}"
        )


        cursor.execute(
            """
            UPDATE users
            SET image=%s
            WHERE id=%s
            """,
            (
                database_path,
                user["id"]
            )
        )


    db.commit()



    # update attendance images

    cursor.execute(
        """
        UPDATE attendance a
        JOIN users u
        ON a.studentID=u.id
        SET a.studentURLImage=u.image
        """
    )


    db.commit()


    cursor.close()

    db.close()


    print("\nDONE")
    print("Images downloaded and database updated")



if __name__ == "__main__":
    main()
