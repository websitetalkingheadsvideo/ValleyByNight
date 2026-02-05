"""
Laws of the Night RAG System - Complete Setup
Run this ONE file and it handles everything.
"""
import subprocess
import sys
import os

def install_package(package):
    """Install a package if it's not already installed."""
    try:
        __import__(package.split('[')[0])
        print(f"✓ {package} already installed")
        return True
    except ImportError:
        print(f"Installing {package}...")
        try:
            subprocess.check_call([sys.executable, "-m", "pip", "install", "--user", package])
            print(f"✓ {package} installed successfully")
            return True
        except Exception as e:
            print(f"✗ Failed to install {package}: {e}")
            return False

def setup_dependencies():
    """Install all required packages."""
    print("="*60)
    print("STEP 1: Installing dependencies")
    print("="*60)
    
    packages = [
        "chromadb",
        "onnxruntime",
    ]
    
    for package in packages:
        if not install_package(package):
            print(f"\nFailed to install {package}. Exiting.")
            input("Press Enter to exit...")
            sys.exit(1)
    
    print("\n✓ All dependencies installed!\n")

def migrate_books():
    """Migrate all JSON books to Chroma database."""
    print("="*60)
    print("STEP 2: Migrating books to vector database")
    print("="*60)
    
    # Import here after packages are installed
    import chromadb
    import json
    
    # Get script directory
    script_dir = os.path.dirname(os.path.abspath(__file__))
    os.chdir(script_dir)
    
    print(f"Working directory: {os.getcwd()}")
    
    # Find Books folder
    books_folder = "Books"
    if not os.path.exists(books_folder):
        print(f"✗ ERROR: '{books_folder}' folder not found in {os.getcwd()}")
        print(f"Available folders: {[d for d in os.listdir('.') if os.path.isdir(d)]}")
        input("\nPress Enter to exit...")
        sys.exit(1)
    
    # Find all JSON files
    json_files = [f for f in os.listdir(books_folder) if f.endswith('.json')]
    
    if not json_files:
        print(f"✗ ERROR: No JSON files found in {books_folder}")
        input("\nPress Enter to exit...")
        sys.exit(1)
    
    print(f"✓ Found {len(json_files)} JSON files")
    for f in json_files:
        print(f"  - {f}")
    
    # Initialize Chroma
    print("\nInitializing database...")
    client = chromadb.PersistentClient(path="./lotn_rag_db")
    
    # Get or create collection
    collection = client.get_or_create_collection(
        name="laws_of_the_night",
        metadata={"description": "Laws of the Night Revised rulebooks"}
    )
    
    print(f"Current database size: {collection.count()} documents")
    
    # Process each JSON file
    total_added = 0
    
    for json_filename in json_files:
        json_path = os.path.join(books_folder, json_filename)
        print(f"\nProcessing: {json_filename}")
        
        try:
            with open(json_path, "r", encoding="utf-8") as f:
                documents = json.load(f)
            
            print(f"  Loaded {len(documents)} documents")
            
            # Prepare data
            ids = []
            texts = []
            metadatas = []
            
            for doc in documents:
                # Create unique ID with book prefix to avoid collisions
                unique_id = f"{doc['metadata']['book_code']}_{doc['id']}"
                ids.append(unique_id)
                texts.append(doc["content"])
                
                metadata = {
                    "source": doc["metadata"]["source"],
                    "page": doc["page"],
                    "content_type": doc["content_type"],
                    "book_code": doc["metadata"]["book_code"],
                    "section_title": doc["metadata"].get("section_title", ""),
                }
                metadatas.append(metadata)
            
            # Add to database
            try:
                collection.add(
                    ids=ids,
                    documents=texts,
                    metadatas=metadatas
                )
                print(f"  ✓ Added {len(ids)} documents")
                total_added += len(ids)
            except Exception as e:
                if "already exists" in str(e).lower():
                    print(f"  ⚠ Skipped (already in database)")
                else:
                    print(f"  ✗ Error adding to database: {e}")
        
        except Exception as e:
            print(f"  ✗ Error processing file: {e}")
            continue
    
    final_count = collection.count()
    print("\n" + "="*60)
    print(f"MIGRATION COMPLETE!")
    print(f"Total documents in database: {final_count}")
    print(f"Newly added: {total_added}")
    print("="*60)

def test_database():
    """Test the database with a sample query."""
    print("\n" + "="*60)
    print("STEP 3: Testing database")
    print("="*60)
    
    import chromadb
    
    client = chromadb.PersistentClient(path="./lotn_rag_db")
    
    try:
        collection = client.get_collection(name="laws_of_the_night")
        count = collection.count()
        
        print(f"✓ Database has {count} documents")
        
        if count > 0:
            print("\nRunning test query: 'What are clan weaknesses?'")
            results = collection.query(
                query_texts=["What are clan weaknesses?"],
                n_results=3
            )
            
            print("\n--- Top 3 Results ---")
            for i, (doc, meta) in enumerate(zip(results['documents'][0], results['metadatas'][0])):
                print(f"\n{i+1}. Source: {meta['source']} (Page {meta['page']})")
                print(f"   {doc[:200]}...")
            
            print("\n✓ Database is working correctly!")
        else:
            print("✗ Database is empty - migration may have failed")
    
    except Exception as e:
        print(f"✗ Error testing database: {e}")

def main():
    """Main setup routine."""
    print("\n" + "="*60)
    print("LAWS OF THE NIGHT - RAG SYSTEM SETUP")
    print("="*60)
    print("\nThis will:")
    print("1. Install required Python packages")
    print("2. Load all your JSON books into a vector database")
    print("3. Test that everything works")
    print("\nThis is a ONE-TIME setup. After this, queries are instant.")
    
    input("\nPress Enter to begin setup...")
    
    try:
        setup_dependencies()
        migrate_books()
        test_database()
        
        print("\n" + "="*60)
        print("✓ SETUP COMPLETE!")
        print("="*60)
        print("\nYour RAG system is ready to use.")
        print("Database location: ./lotn_rag_db")
        
    except KeyboardInterrupt:
        print("\n\nSetup cancelled by user.")
    except Exception as e:
        print(f"\n\n✗ Setup failed with error: {e}")
        import traceback
        traceback.print_exc()
    
    input("\n\nPress Enter to exit...")

if __name__ == "__main__":
    main()