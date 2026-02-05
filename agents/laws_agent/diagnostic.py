import chromadb
import json
import os
import traceback

try:
    # Get the current working directory
    current_dir = os.getcwd()
    print(f"Current directory: {current_dir}")

    # Try to find the Books folder
    books_folder = os.path.join(current_dir, "Books")

    # Check if Books folder exists
    if not os.path.exists(books_folder):
        print(f"Books folder not found at: {books_folder}")
        print(f"Files/folders here: {os.listdir(current_dir)}")
    else:
        print(f"✓ Found Books folder: {books_folder}")
        
        # List JSON files in Books folder
        json_files = [f for f in os.listdir(books_folder) if f.endswith('.json')]
        print(f"JSON files found: {json_files}")

        if json_files:
            # Load the first one
            json_file = os.path.join(books_folder, json_files[0])
            print(f"\nLoading: {json_file}")

            with open(json_file, "r", encoding="utf-8") as f:
                documents = json.load(f)

            print(f"Loaded {len(documents)} documents")

            # Initialize Chroma
            client = chromadb.PersistentClient(path="./lotn_rag_db")
            collection = client.get_or_create_collection(
                name="laws_of_the_night",
                metadata={"description": "Laws of the Night Revised rulebooks"}
            )

            # Prepare data
            ids = []
            texts = []
            metadatas = []

            for doc in documents:
                ids.append(doc["id"])
                texts.append(doc["content"])
                
                metadata = {
                    "source": doc["metadata"]["source"],
                    "page": doc["page"],
                    "content_type": doc["content_type"],
                    "book_code": doc["metadata"]["book_code"],
                }
                metadatas.append(metadata)

            # Add to Chroma
            print("Adding to Chroma...")
            collection.add(
                ids=ids,
                documents=texts,
                metadatas=metadatas
            )

            print(f"✓ Successfully added {len(ids)} documents!")
            print(f"Total in DB: {collection.count()}")

except Exception as e:
    print(f"\n!!! ERROR !!!")
    print(f"{e}")
    traceback.print_exc()

finally:
    print("\n" + "="*50)
    print("Script finished. Window will stay open.")
    print("="*50)
    input("\nPress ANY KEY to exit...")
    