package com.piwik.intellijplugins.piwikstorm.services.impl;

import com.google.common.cache.CacheBuilder;
import com.google.common.cache.CacheLoader;
import com.google.common.cache.LoadingCache;
import com.intellij.openapi.util.Pair;
import com.intellij.psi.PsiElement;
import com.intellij.psi.PsiRecursiveElementWalkingVisitor;
import com.jetbrains.php.lang.documentation.phpdoc.psi.PhpDocComment;
import com.jetbrains.php.lang.documentation.phpdoc.psi.tags.PhpDocTag;
import com.jetbrains.php.lang.psi.elements.*;
import com.piwik.intellijplugins.piwikstorm.services.PiwikPsiElementMetadataProvider;

import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ExecutionException;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class PiwikPsiElementMetadataProviderImpl implements PiwikPsiElementMetadataProvider {

    public static final String API_TAG = "@api";
    public static final int MAX_ELEMENTS_IN_API_ANNOTATION_CACHE = 4096;

    private class CheckIfChildrenHaveApi extends PsiRecursiveElementWalkingVisitor {
        public boolean childHasApi = false;

        @Override
        public void visitElement(PsiElement element) {
            if (element instanceof PhpNamedElement) {
                this.childHasApi = this.childHasApi
                        || PiwikPsiElementMetadataProviderImpl.this.isElementMarkedWithApi((PhpNamedElement) element);
            }
        }
    }

    /**
     * Regex pattern used to extract the plugin name and/or plugin base namespace from a
     * PhpNamedElement's namespace.
     */
    private static final Pattern pluginNameExtractionRegex = Pattern.compile("\\\\?Piwik\\\\Plugins\\\\([^\\\\]+)\\\\");

    /**
     * Guava cache that holds whether a PhpNamedElement is exposed as part of Piwik's @api.
     * Stores both a boolean for whether the element is @api and a long that is the
     * modification timestamp for the element's file. The modification timestamp is used
     * to determine if an element has been modified since it was last cached.
     */
    private LoadingCache<PhpNamedElement, Pair<Long, Boolean>> isMarkedWithApiAnnotationCache;

    /**
     * Constructor.
     */
    PiwikPsiElementMetadataProviderImpl() {
        this.isMarkedWithApiAnnotationCache = CacheBuilder.newBuilder()
            .maximumSize(MAX_ELEMENTS_IN_API_ANNOTATION_CACHE)
            .build(
                    new CacheLoader<PhpNamedElement, Pair<Long, Boolean>>() {
                        @Override
                        public Pair<Long, Boolean> load(PhpNamedElement element) throws Exception {
                            boolean isMarkedWithApi = PiwikPsiElementMetadataProviderImpl.this.isMarkedWithApiNonCached(element);
                            return Pair.create(element.getContainingFile().getModificationStamp(), isMarkedWithApi);
                        }
                    }
            );
    }

    public String getPluginNamespaceOfElement(PsiElement element) {
        PhpNamedElement closestNamedElement = this.getClosestElementAncestor(element, PhpNamedElement.class);
        return this.matchNamespaceAgainstPluginNameExtractionRegex(closestNamedElement, 0);
    }

    public String getPluginNameOfElement(PsiElement element) {
        PhpNamedElement closestNamedElement = this.getClosestElementAncestor(element, PhpNamedElement.class);
        return this.matchNamespaceAgainstPluginNameExtractionRegex(closestNamedElement, 1);
    }

    public boolean isMarkedWithApi(PhpNamedElement element) {
        long lastModifiedTime = element.getContainingFile().getModificationStamp();

        try {
            Pair<Long, Boolean> result = this.isMarkedWithApiAnnotationCache.get(element);

            // if element has been changed since the cache item has been set, invalidate the cache item
            // and re-compute whether the element is @api
            if (result.getFirst() <= lastModifiedTime) {
                this.isMarkedWithApiAnnotationCache.invalidate(element);
                result = this.isMarkedWithApiAnnotationCache.get(element);
            }

            return result.getSecond();
        } catch (ExecutionException ex) {
            throw new RuntimeException("isMarkedWithApiAnnotationCache failed unexpectedly", ex);
        }
    }

    private boolean isMarkedWithApiNonCached(PhpNamedElement element) {
        boolean isElementTaggedWithApi = this.isElementMarkedWithApi(element);
        if (isElementTaggedWithApi) {
            return true;
        }

        if (element instanceof PhpClass) {
            // classes/interfaces/traits are also marked as @api if any named element within the class
            // has the @api annotation
            CheckIfChildrenHaveApi visitor = new CheckIfChildrenHaveApi();
            element.acceptChildren(visitor);

            if (visitor.childHasApi) {
                return true;
            } else {
                // classes/interfaces are marked as @api if a base type is marked as @api
                List<PhpNamedElement> baseTypesToCheck = this.getBaseTypesToCheckForApi((PhpClass)element);

                for (PhpNamedElement ancestor : baseTypesToCheck) {
                    if (this.isMarkedWithApi(ancestor)) {
                        return true;
                    }
                }
            }
        } else if (element instanceof PhpClassMember) {
            // methods/fields/etc. are also marked as @api if the class/interface/trait above it has the @api annotation
            PhpClass closestClass = this.getClosestElementAncestor(element, PhpClass.class);
            if (closestClass != null && this.isElementMarkedWithApi(closestClass)) {
                return true;
            }

            // members are marked as @api if there exists parent method w/ same signature that is @api (including interfaces)
            List<PhpNamedElement> baseMembersToCheck = this.getBaseMembersToCheckForApi((PhpClassMember)element);
            for (PhpNamedElement ancestorMember : baseMembersToCheck) {
                if (this.isMarkedWithApi(ancestorMember)) {
                    return true;
                }
            }
        }

        return false;
    }

    private List<PhpNamedElement> getBaseTypesToCheckForApi(PhpClass element) {
        // classes are not @api if they implement @api interfaces, so we only get the extends list TODO: add test for this
        ArrayList<PhpNamedElement> result = new ArrayList<PhpNamedElement>();

        ExtendsList extendsList = element.getExtendsList();
        if (extendsList == null) {
            return result;
        }

        List<ClassReference> extendsReferences = extendsList.getReferenceElements();
        if (extendsReferences == null) {
            return result;
        }

        for (ClassReference ref : extendsList.getReferenceElements()) {
            PsiElement ancestor = ref.resolve();
            if (ancestor instanceof PhpNamedElement) {
                result.add((PhpNamedElement) ancestor);
            }
        }

        return result;
    }

    private List<PhpNamedElement> getBaseMembersToCheckForApi(PhpClassMember element) {
        ArrayList<PhpNamedElement> result = new ArrayList<PhpNamedElement>();
        // TODO
        return result;
    }

    private boolean isElementMarkedWithApi(PhpNamedElement element) {
        PhpDocComment docComment = element.getDocComment();
        if (docComment != null) {
            PhpDocTag[] elements = docComment.getTagElementsByName(API_TAG);
            if (elements.length != 0) {
                return true;
            }
        }
        return false;
    }

    private <T> T getClosestElementAncestor(PsiElement element, Class<T> klass) {
        while (element != null && !(klass.isInstance(element))) {
            element = element.getParent();
        }

        //noinspection unchecked
        return (T)element;
    }

    private String matchNamespaceAgainstPluginNameExtractionRegex(PhpNamedElement element, int groupIndexToReturn) {
        if (element == null) {
            return null;
        }

        String namespace = element.getNamespaceName();

        Matcher m = pluginNameExtractionRegex.matcher(namespace);
        if (m.matches()) {
            return m.group(groupIndexToReturn);
        } else {
            return null;
        }
    }
}